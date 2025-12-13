<?php

namespace Database\Seeders;

use App\Models\PcPart;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PcPartSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the PC parts table from CSV.
     */
    public function run(): void
    {
        $csvPath = base_path('pc_parts.csv');

        if (!file_exists($csvPath)) {
            $this->command->error("CSV file not found at: {$csvPath}");
            return;
        }

        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            $this->command->error("Unable to open CSV file");
            return;
        }

        // Read header
        $header = fgetcsv($handle);
        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);

            $price = $this->convertPrice($data['Price'] ?? '0');

            PcPart::create([
                'type' => $data['Type'] ?? 'Unknown',
                'external_id' => $data['ID'] ?? '',
                'vendor' => $data['Vendor'] ?? '',
                'title' => $data['Title'] ?? '',
                'price' => $price,
                'image' => $data['Image'] ?? '',
                'link' => $data['Link'] ?? '',
            ]);
            $count++;
        }

        fclose($handle);

        $this->command->info("Successfully seeded {$count} PC parts from CSV");
    }

    /**
     * Convert price string to float
     * Handles formats like "₱11,200.00", "$19.99", etc.
     */
    private function convertPrice($priceText)
    {
        // Remove all non-numeric characters except decimal point
        $cleaned = preg_replace('/[^0-9.]/', '', $priceText);
        return round((float) $cleaned, 2);
    }
}
