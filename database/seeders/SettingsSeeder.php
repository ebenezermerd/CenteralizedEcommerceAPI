<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // General settings
        $generalSettings = [
            [
                'key' => 'app_name',
                'value' => 'Korecha E-commerce',
                'group' => 'general',
                'description' => 'Application name',
            ],
            [
                'key' => 'app_description',
                'value' => 'Korecha is a multi-vendor e-commerce platform for Ethiopian businesses.',
                'group' => 'general',
                'description' => 'Application description',
            ],
            [
                'key' => 'contact_email',
                'value' => 'info@korecha.com.et',
                'group' => 'general',
                'description' => 'Contact email address',
            ],
            [
                'key' => 'contact_phone',
                'value' => '+251 911 234567',
                'group' => 'general',
                'description' => 'Contact phone number',
            ],
        ];

        // Agreement settings
        $agreementSettings = [
            [
                'key' => 'supplier_agreement',
                'value' => "<h3>1. Revenue Sharing and Payment Terms</h3>
<ul>
  <li>The Supplier agrees to a revenue-sharing model where Korecha will retain a percentage of each sale.</li>
  <li>Korecha reserves the right to deduct any applicable taxes, service fees, or other charges before revenue distribution to the Supplier.</li>
</ul>

<h3>2. Invoicing and Financial Reporting</h3>
<ul>
  <li>Korecha will generate all customer-facing invoices, which will reflect product details, prices, taxes, and any other applicable charges.</li>
  <li>The Supplier acknowledges that Korecha will provide periodic reports (weekly/monthly) summarizing the sales performance, revenue generated, and payment distributions.</li>
  <li>The Supplier is responsible for keeping a record of these reports for tax or financial reporting purposes.</li>
</ul>

<h3>3. Product Listings and Compliance</h3>
<ul>
  <li>The Supplier is responsible for maintaining accurate product details on the platform, including stock levels, pricing, descriptions, and other relevant information.</li>
  <li>The Supplier agrees that all products listed on the platform must comply with all applicable laws, regulations, and standards, including but not limited to safety standards, consumer protection laws, and import/export regulations.</li>
  <li>Korecha reserves the right to remove or modify any product listings that do not meet these standards.</li>
</ul>

<h3>4. Order Fulfillment</h3>
<ul>
  <li>The Supplier is responsible for timely and accurate order fulfillment. Korecha will assign orders based on criteria such as supplier location and customer needs.</li>
  <li>The Supplier agrees to comply with Korecha's order fulfillment timelines, packaging standards, and quality control policies.</li>
  <li>In case of any issues preventing fulfillment, the Supplier must notify Korecha immediately.</li>
</ul>

<h3>5. Confidentiality and Data Protection</h3>
<ul>
  <li>The Supplier consents to Korecha collecting, processing, and storing data necessary for managing orders, revenue sharing, and performance tracking.</li>
  <li>All customer data is confidential and solely managed by Korecha. The Supplier agrees not to retain, use, or disclose any customer data unless expressly authorized by Korecha.</li>
</ul>",
                'group' => 'agreement',
                'description' => 'Supplier agreement text',
            ],
        ];

        // Insert all settings
        foreach (array_merge($generalSettings, $agreementSettings) as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
