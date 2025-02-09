<?php

namespace App\Http\Requests;

use App\Traits\ProductFieldMapper;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Category;
use App\Models\Product;

class ProductRequest extends FormRequest
{
    use ProductFieldMapper;

    protected ?Product $existingProduct = null;

    protected function getExistingProduct()
    {
        if ($this->existingProduct === null) {
            $this->existingProduct = Product::where('sku', $this->sku)
                ->orWhere('code', $this->code)
                ->first();
        }
        return $this->existingProduct;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'sku' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $existing = Product::where('sku', $value)->first();

                    // If we found a product and it's not the one we're updating
                    if ($existing && (!$this->getExistingProduct() || $existing->id !== $this->getExistingProduct()->id)) {
                        $fail('This SKU already exists!');
                    }
                },
            ],
            'code' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $existing = Product::where('code', $value)->first();

                    // If we found a product and it's not the one we're updating
                    if ($existing && (!$this->getExistingProduct() || $existing->id !== $this->getExistingProduct()->id)) {
                        $fail('This code already exists!');
                    }
                },
            ],
            'price' => 'required|numeric|min:1',
            'priceSale' => 'nullable|numeric|min:0',
            'taxes' => 'nullable|numeric|min:0',
            'coverUrl' => ['required', function ($attribute, $value, $fail) {
                // Allow both URLs and files
                if (!is_string($value) && !is_file($value)) {
                    $fail('Cover image must be either a URL or a file');
                    return;
                }

                // Only validate mime types if it's a file
                if (is_file($value)) {
                    $mimes = ['jpeg', 'png', 'jpg', 'gif'];
                    $validator = validator([$attribute => $value], [
                        $attribute => 'file|mimes:' . implode(',', $mimes)
                    ]);
                    if ($validator->fails()) {
                        $fail($validator->errors()->first());
                    }
                } else if (is_string($value)) {
                    // Validate URL format if it's a string
                    if (!filter_var($value, FILTER_VALIDATE_URL)) {
                        $fail('Cover image URL must be a valid URL');
                    }
                }
            }],
            'images' => 'required|array',
            'images.*' => [function ($attribute, $value, $fail) {
                \Log::info("Validating image: ", ['attribute' => $attribute, 'value' => $value]);

                // Keep existing image validation
                if (!is_string($value) && !is_file($value)) {
                    \Log::error("Image validation failed: Not a string or file");
                    $fail('Image must be either a URL or a file');
                    return;
                }

                if (is_file($value)) {
                    $mimes = ['jpeg', 'png', 'jpg', 'gif'];
                    $validator = validator([$attribute => $value], [
                        $attribute => 'file|mimes:' . implode(',', $mimes)
                    ]);

                    if ($validator->fails()) {
                        \Log::error("Image file validation failed: " . $validator->errors()->first());
                        $fail($validator->errors()->first());
                    } else {
                        \Log::info("Image file validation passed", [
                            'mime_type' => $value->getMimeType(),
                            'size' => $value->getSize()
                        ]);
                    }
                } else if (is_string($value)) {
                    if (!filter_var($value, FILTER_VALIDATffE_URL)) {
                        \Log::error("Image URL validation failed: Invalid URL format");
                        $fail('Image URL must be a valid URL');
                    } else {
                        \Log::info("Image URL validation passed", ['url' => $value]);
                    }
                }
            }],
            'description' => 'required|string',
            'subDescription' => 'nullable|string',
            'quantity' => 'required|integer|min:1',
            'sizes' => 'required|array|min:1',
            'sizes.*' => 'string',
            'colors' => 'required|array|min:1',
            'colors.*' => 'string',
            'tags' => 'required|array|min:1',
            'tags.*' => 'string',
            'gender' => 'required|array|min:1',
            'gender.*' => 'string',
            'category' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    // Get the category name and remove any quotes
                    $selectedCategory = trim($value, '"');
                    if (!Category::findByName($selectedCategory)) {
                        $fail("You selected '{$selectedCategory}'. This category is invalid. Available categories: " .
                            Category::pluck('name')->implode(', '));
                    }
                }
            ],
            'publish' => 'required|in:draft,published',
            'saleLabel.enabled' => 'boolean',
            'saleLabel.content' => 'string|nullable',
            'newLabel.enabled' => 'boolean',
            'newLabel.content' => 'string|nullable',
        ];

        // Remove the additional coverUrl rule for new products since it's handled in the closure
        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required!',
            'name.string' => 'Name must be a string!',
            'sku.required' => 'Product SKU is required!',
            'sku.unique' => 'This SKU already exists!',
            'code.required' => 'Product code is required!',
            'code.unique' => 'This code already exists!',
            'price.required' => 'Price is required!',
            'price.min' => 'Price should not be less than $1.00',
            'coverUrl.required' => 'Cover image is required!',
            'coverUrl.file' => 'Cover image must be a file!',
            'coverUrl.mimes' => 'Cover image must be jpeg, png, jpg, or gif!',
            'images.required' => 'Additional images are required!',
            'images.array' => 'Images must be an array!',
            'sizes.required' => 'Sizes are required!',
            'sizes.min' => 'Choose at least one size!',
            'tags.required' => 'Tags are required!',
            'tags.min' => 'Must have at least 2 tags!',
            'gender.required' => 'Gender is required!',
            'gender.min' => 'Choose at least one gender option!',
            'description.required' => 'Description is required!',
            'quantity.required' => 'Quantity is required!',
            'quantity.min' => 'Quantity must be at least 1!',
            'category.required' => 'Category is required!',
            'category.exists' => 'The selected category does not exist!',
            'publish.required' => 'Publication status is required!',
            'publish.in' => 'Publication status must be either "draft" or "published". You provided: ":input"',
        ];
    }

    protected function prepareForValidation()
    {
        // Get existing product if updating
        if ($this->sku || $this->code) {
            $this->existingProduct = $this->getExistingProduct();
            if ($this->existingProduct) {
                $this->merge(['id' => $this->existingProduct->id]);
            }
        }
        // Log raw request data
        \Log::info('Raw product request data:', $this->all());

        // Clean up publish value if it exists
        if (isset($this->publish)) {
            $this->merge([
                'publish' => trim($this->publish, '"') // Remove any surrounding quotes
            ]);
        }

        // Convert string boolean to actual boolean
        if (isset($this->isPublished)) {
            $this->merge([
                'isPublished' => filter_var($this->isPublished, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        // Handle JSON strings for arrays if they're sent as strings
        foreach (['tags', 'sizes', 'colors', 'gender'] as $field) {
            if (is_string($this->$field)) {
                $this->merge([$field => json_decode($this->$field, true)]);
            }
        }

        // Convert numbers from strings if needed
        foreach (['price', 'priceSale', 'taxes', 'quantity'] as $field) {
            if (isset($this->$field) && is_string($this->$field)) {
                $this->merge([$field => (float) $this->$field]);
            }
        }

        // Handle cover URL and images consistently
        $processImageUrl = function($value) {
            if (is_file($value)) {
                return $value->store('products/covers', 'public');
            }
            return is_string($value) ? $value : null;
        };

        $coverUrl = $this->has('coverUrl') ? $processImageUrl($this->coverUrl) : null;

        // Process images array
        $processImagesPath = function ($images) {
            $processedImages = [];
            foreach ($images as $value) {
                if (is_file($value)) {
                    $path = $value->store('products/images', 'public');
                    $processedImages[] = $path;
                }
                else if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                    $processedImages[] = $value;
                }
                else {
                    // Log the error and return a default image
                    \Log::error('Invalid image provided', ['image' => $value]);
                    $processedImages[] = 'products/default-image.png';
                }
            }
            return $processedImages;
        };

       // Override only the 'images' key

       $images = $this->has('images') ? $processImagesPath($this->images) : null;

       $saleLabel = is_string($this->saleLabel) ? json_decode($this->saleLabel, true) : ($this->saleLabel ?? ['enabled' => false]);
       $newLabel = is_string($this->newLabel) ? json_decode($this->newLabel, true) : ($this->newLabel ?? ['enabled' => false]);


        $mappedData = $this->mapToDatabase([
            'name' => $this->name,
            'sku' => $this->sku,
            'code' => $this->code,
            'price' => $this->price,
            'category' => $this->category,
            'priceSale' => $this->priceSale,
            'subDescription' => $this->subDescription,
            'coverUrl' => $coverUrl ?? (!$this->id ? 'products/default-cover.png' : null),
            'images' => $images,
            'taxes' => $this->taxes,
            'tags' => is_string($this->tags) ? json_decode($this->tags, true) : $this->tags,
            'sizes' => is_string($this->sizes) ? json_decode($this->sizes, true) : $this->sizes,
            'colors' => is_string($this->colors) ? json_decode($this->colors, true) : $this->colors,
            'gender' => is_string($this->gender) ? json_decode($this->gender, true) : $this->gender,
            'publish' => trim($this->publish ?? 'draft', '"'),
            'newLabel' => $newLabel,
            'saleLabel' => $saleLabel,
            'quantity' => $this->quantity,
        ]);

        \Log::info('Product request mapped  images path data:', $mappedData);

        $this->merge($mappedData);
    }
}
