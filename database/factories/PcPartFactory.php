<?php

namespace Database\Factories;

use App\Models\PcPart;
use Illuminate\Database\Eloquent\Factories\Factory;

class PcPartFactory extends Factory
{
    protected $model = PcPart::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement([
                'Processors',
                'Motherboards',
                'Memory',
                'Storage',
                'Graphics Cards',
                'Power Supply',
                'Cases',
                'Cooling'
            ]),
            'external_id' => $this->faker->unique()->numerify('##########'),
            'vendor' => $this->faker->randomElement(['Intel', 'AMD', 'Nvidia', 'ASUS', 'MSI', 'Gigabyte', 'ASRock', 'Corsair', 'G.Skill', 'Kingston', 'Samsung', 'WD', 'Seagate']),
            'title' => $this->faker->words(4, true),
            'price' => $this->faker->randomFloat(2, 1000, 50000),
            'image' => 'https://pcx.com.ph/cdn/shop/files/placeholder.jpg',
            'link' => '/products/' . $this->faker->slug(),
        ];
    }

    /**
     * Create a processor part
     */
    public function processor(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'Processors',
                'vendor' => $this->faker->randomElement(['Intel', 'AMD']),
                'title' => $this->faker->randomElement(['Intel® Core™ i5', 'Intel® Core™ i7', 'AMD Ryzen™ 5', 'AMD Ryzen™ 7']) . ' ' . $this->faker->numerify('###'),
                'price' => $this->faker->randomFloat(2, 5000, 30000),
            ];
        });
    }

    /**
     * Create a motherboard part
     */
    public function motherboard(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'Motherboards',
                'vendor' => $this->faker->randomElement(['ASUS', 'MSI', 'Gigabyte', 'ASRock']),
                'title' => $this->faker->randomElement(['ASUS PRIME', 'MSI PRO', 'Gigabyte B', 'ASRock']) . ' ' . $this->faker->bothify('????#'),
                'price' => $this->faker->randomFloat(2, 3000, 15000),
            ];
        });
    }

    /**
     * Create a GPU part
     */
    public function gpu(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'Graphics Cards',
                'vendor' => $this->faker->randomElement(['Nvidia', 'AMD', 'ASUS', 'MSI', 'Gigabyte']),
                'title' => $this->faker->randomElement(['GeForce RTX', 'Radeon RX']) . ' ' . $this->faker->numerify('####'),
                'price' => $this->faker->randomFloat(2, 10000, 50000),
            ];
        });
    }

    /**
     * Create a RAM part
     */
    public function ram(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'Memory',
                'vendor' => $this->faker->randomElement(['Corsair', 'G.Skill', 'Kingston', 'ADATA']),
                'title' => $this->faker->randomElement(['DDR4', 'DDR5']) . ' ' . $this->faker->numerify('#### MHz'),
                'price' => $this->faker->randomFloat(2, 2000, 8000),
            ];
        });
    }

    /**
     * Create a storage part
     */
    public function storage(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'Storage',
                'vendor' => $this->faker->randomElement(['Samsung', 'WD', 'Seagate', 'Kingston', 'Crucial']),
                'title' => $this->faker->randomElement(['SSD NVMe', 'HDD 3.5"', 'SSD SATA']) . ' ' . $this->faker->numerify('###') . 'GB',
                'price' => $this->faker->randomFloat(2, 1000, 10000),
            ];
        });
    }

    /**
     * Create a PSU part
     */
    public function psu(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'Power Supply',
                'vendor' => $this->faker->randomElement(['Corsair', 'EVGA', 'Seasonic', 'FSP']),
                'title' => $this->faker->numerify('###') . 'W Power Supply',
                'price' => $this->faker->randomFloat(2, 2000, 8000),
            ];
        });
    }

    /**
     * Create a case part
     */
    public function pcCase(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'Cases',
                'vendor' => $this->faker->randomElement(['Corsair', 'NZXT', 'Lian Li', 'Fractal Design']),
                'title' => 'Computer Case ' . $this->faker->bothify('???-###'),
                'price' => $this->faker->randomFloat(2, 2000, 8000),
            ];
        });
    }

    /**
     * Create a CPU cooler part
     */
    public function cpuCooler(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'Cooling',
                'vendor' => $this->faker->randomElement(['Noctua', 'Be Quiet!', 'Corsair', 'ARCTIC']),
                'title' => $this->faker->randomElement(['Air Cooler', 'Liquid Cooler']) . ' ' . $this->faker->bothify('???-###'),
                'price' => $this->faker->randomFloat(2, 1000, 5000),
            ];
        });
    }
}
