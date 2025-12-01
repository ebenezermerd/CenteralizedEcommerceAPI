<?php

namespace App\Services;

use HubSpot\Factory;
use HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInput as ContactInput;
use HubSpot\Client\Crm\Products\Model\SimplePublicObjectInput as ProductInput;
use HubSpot\Client\Crm\Deals\Model\SimplePublicObjectInput as DealInput;
use HubSpot\Client\Crm\LineItems\Model\SimplePublicObjectInput as LineItemInput;
use HubSpot\Client\Crm\Contacts\Model\Filter as ContactFilter;
use HubSpot\Client\Crm\Contacts\Model\FilterGroup as ContactFilterGroup;
use HubSpot\Client\Crm\Contacts\Model\PublicObjectSearchRequest as ContactSearchRequest;
use HubSpot\Client\Crm\Products\Model\Filter as ProductFilter;
use HubSpot\Client\Crm\Products\Model\FilterGroup as ProductFilterGroup;
use HubSpot\Client\Crm\Products\Model\PublicObjectSearchRequest as ProductSearchRequest;
use HubSpot\Client\Crm\Deals\Model\Filter as DealFilter;
use HubSpot\Client\Crm\Deals\Model\FilterGroup as DealFilterGroup;
use HubSpot\Client\Crm\Deals\Model\PublicObjectSearchRequest as DealSearchRequest;
use Illuminate\Support\Facades\Log;

class HubSpotService
{
    protected $hubspot;

    public function __construct()
    {
        $accessToken = config('services.hubspot.access_token');
        if ($accessToken) {
            $this->hubspot = Factory::createWithAccessToken($accessToken);
        }
    }

    public function syncContact($user)
    {
        if (!$this->hubspot) return;

        $properties = [
            'email' => $user->email,
            'firstname' => $user->firstName,
            'lastname' => $user->lastName,
            'phone' => $user->phone,
            'city' => $user->city,
            'country' => $user->country,
            'zip' => $user->zip_code,
        ];

        $contactInput = new ContactInput();
        $contactInput->setProperties($properties);

        try {
            // Try to search for existing contact by email
            $filter = new ContactFilter();
            $filter->setOperator('EQ');
            $filter->setPropertyName('email');
            $filter->setValue($user->email);

            $filterGroup = new ContactFilterGroup();
            $filterGroup->setFilters([$filter]);

            $searchRequest = new ContactSearchRequest();
            $searchRequest->setFilterGroups([$filterGroup]);

            $searchResults = $this->hubspot->crm()->contacts()->searchApi()->doSearch($searchRequest);

            if ($searchResults->getTotal() > 0) {
                // Update existing
                $contactId = $searchResults->getResults()[0]->getId();
                $this->hubspot->crm()->contacts()->basicApi()->update($contactId, $contactInput);
                return $contactId;
            } else {
                // Create new
                $contact = $this->hubspot->crm()->contacts()->basicApi()->create($contactInput);
                return $contact->getId();
            }
        } catch (\Exception $e) {
            Log::error('HubSpot Contact Sync Error: ' . $e->getMessage());
        }
    }

    public function syncProduct($product)
    {
        if (!$this->hubspot) return;

        $properties = [
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'hs_sku' => $product->sku,
        ];

        $productInput = new ProductInput();
        $productInput->setProperties($properties);

        try {
             // Search by SKU if available
             if ($product->sku) {
                $filter = new ProductFilter();
                $filter->setOperator('EQ');
                $filter->setPropertyName('hs_sku');
                $filter->setValue($product->sku);

                $filterGroup = new ProductFilterGroup();
                $filterGroup->setFilters([$filter]);

                $searchRequest = new ProductSearchRequest();
                $searchRequest->setFilterGroups([$filterGroup]);

                $searchResults = $this->hubspot->crm()->products()->searchApi()->doSearch($searchRequest);

                if ($searchResults->getTotal() > 0) {
                    $productId = $searchResults->getResults()[0]->getId();
                    $this->hubspot->crm()->products()->basicApi()->update($productId, $productInput);
                    return $productId;
                }
             }

             // Create if not found
             $hubSpotProduct = $this->hubspot->crm()->products()->basicApi()->create($productInput);
             return $hubSpotProduct->getId();

        } catch (\Exception $e) {
            Log::error('HubSpot Product Sync Error: ' . $e->getMessage());
        }
    }

    public function syncDeal($order)
    {
        if (!$this->hubspot) return;

        // 1. Sync Contact
        $contactId = $this->syncContact($order->user);

        // 2. Create/Update Deal
        $dealName = 'Order #' . $order->order_number;
        $properties = [
            'dealname' => $dealName,
            'amount' => $order->total_amount,
            'dealstage' => 'closedwon', // Default to closed won for completed orders
            'pipeline' => 'default',
            'closedate' => $order->created_at->timestamp * 1000,
        ];

        $dealInput = new DealInput();
        $dealInput->setProperties($properties);

        try {
            // Search for existing deal
            $filter = new DealFilter();
            $filter->setOperator('EQ');
            $filter->setPropertyName('dealname');
            $filter->setValue($dealName);

            $filterGroup = new DealFilterGroup();
            $filterGroup->setFilters([$filter]);

            $searchRequest = new DealSearchRequest();
            $searchRequest->setFilterGroups([$filterGroup]);

            $searchResults = $this->hubspot->crm()->deals()->searchApi()->doSearch($searchRequest);

            if ($searchResults->getTotal() > 0) {
                // Update existing
                $dealId = $searchResults->getResults()[0]->getId();
                $this->hubspot->crm()->deals()->basicApi()->update($dealId, $dealInput);
            } else {
                // Create new
                $deal = $this->hubspot->crm()->deals()->basicApi()->create($dealInput);
                $dealId = $deal->getId();
            }

            // 3. Associate Deal with Contact
            if ($contactId) {
                $this->hubspot->crm()->deals()->associationsApi()->create(
                    $dealId,
                    'contacts',
                    $contactId,
                    [
                        [
                            "associationCategory" => "HUBSPOT_DEFINED",
                            "associationTypeId" => 3 // Deal to Contact
                        ]
                    ]
                );
            }

            // 4. Create Line Items and Associate with Deal
            // Note: Handling line item updates is complex (deleting old ones vs updating). 
            // For simplicity, we assume new deals or we append. 
            // A full sync might require clearing old line items first if updating.
            // Here we only add if it's a new deal or we accept duplicates for now to avoid complexity.
            if ($searchResults->getTotal() == 0) {
                foreach ($order->items as $item) {
                    $productId = $this->syncProduct($item->product); // Ensure product exists
                    
                    if ($productId) {
                        $lineItemProperties = [
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'name' => $item->product->name,
                        ];
                        
                        $lineItemInput = new LineItemInput();
                        $lineItemInput->setProperties($lineItemProperties);
                        
                        $lineItem = $this->hubspot->crm()->lineItems()->basicApi()->create($lineItemInput);
                        $lineItemId = $lineItem->getId();

                        // Associate Line Item with Deal
                        $this->hubspot->crm()->lineItems()->associationsApi()->create(
                            $lineItemId,
                            'deals',
                            $dealId,
                            [
                                [
                                    "associationCategory" => "HUBSPOT_DEFINED",
                                    "associationTypeId" => 20 // Line Item to Deal
                                ]
                            ]
                        );
                        
                        // Associate Line Item with Product
                        $this->hubspot->crm()->lineItems()->associationsApi()->create(
                            $lineItemId,
                            'products',
                            $productId,
                            [
                                [
                                    "associationCategory" => "HUBSPOT_DEFINED",
                                    "associationTypeId" => 21 // Line Item to Product
                                ]
                            ]
                        );
                    }
                }
            }

            return $dealId;

        } catch (\Exception $e) {
            Log::error('HubSpot Deal Sync Error: ' . $e->getMessage());
        }
    }
}
