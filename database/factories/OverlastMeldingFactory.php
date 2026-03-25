<?php

namespace Database\Factories;

use App\Models\OverlastMelding;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OverlastMelding>
 */
class OverlastMeldingFactory extends Factory
{
    protected $model = OverlastMelding::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'categorie' => $this->faker->randomElement(OverlastMelding::categories()),
            'beschrijving' => $this->faker->paragraph(),
            'melding_datum_tijd' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'aantal_vissen' => $this->faker->optional(0.3)->numberBetween(1, 50),
            'ernst_situatie' => $this->faker->optional(0.4)->randomElement([
                OverlastMelding::ERNST_LAAG,
                OverlastMelding::ERNST_MIDDEN,
                OverlastMelding::ERNST_HOOG,
            ]),
            'locatie_adres' => $this->faker->address(),
            'locatie_details' => [
                'latitude' => $this->faker->latitude(52, 53),  // Noord Holland regio
                'longitude' => $this->faker->longitude(4, 6),
                'address' => $this->faker->address(),
            ],
            'fotos' => $this->faker->optional(0.3)->randomElements([
                'melding_001.jpg',
                'melding_002.jpg',
                'melding_003.jpg',
            ], 1),
            'melder_naam' => $this->faker->optional(0.5)->name(),
            'melder_email' => $this->faker->optional(0.6)->safeEmail(),
            'melder_telefoon' => $this->faker->optional(0.4)->phoneNumber(),
            'melder_anoniem' => $this->faker->boolean(20),  // 20% anoniem
            'categorie_scope_geldig' => $this->faker->boolean(85),  // 85% geldig
            'captcha_token' => $this->faker->sha256(),
            'status' => $this->faker->randomElement(OverlastMelding::statuses()),
            'interne_notities' => $this->faker->optional(0.4)->text(),
            'afgewezen_reden' => null,  // Set null by default
            'verwerkt_door' => $this->faker->optional(0.5)->randomElement(
                User::query()->pluck('id')->toArray()
            ),
            'verwerkt_op' => $this->faker->optional(0.3)->dateTimeBetween('-7 days', 'now'),
        ];
    }

    /**
     * State: Melding met geldige scope
     */
    public function geldigeBereik(): static
    {
        return $this->state(fn (array $attributes) => [
            'categorie_scope_geldig' => true,
        ]);
    }

    /**
     * State: Melding met ongeldige scope
     */
    public function ongelidgeBereik(): static
    {
        return $this->state(fn (array $attributes) => [
            'categorie_scope_geldig' => false,
        ]);
    }

    /**
     * State: Nieuwe melding
     */
    public function new(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OverlastMelding::STATUS_NIEUW,
            'verwerkt_door' => null,
            'verwerkt_op' => null,
        ]);
    }

    /**
     * State: Afgewezen melding
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OverlastMelding::STATUS_AFGEWEZEN,
            'afgewezen_reden' => $this->faker->text(100),
            'verwerkt_door' => User::inRandomOrder()->first()->id ?? 1,
            'verwerkt_op' => now(),
        ]);
    }

    /**
     * State: Anonieme melding
     */
    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'melder_anoniem' => true,
            'melder_naam' => null,
            'melder_email' => null,
            'melder_telefoon' => null,
        ]);
    }

    /**
     * State: Met fotos
     */
    public function withPhotos(): static
    {
        return $this->state(fn (array $attributes) => [
            'fotos' => [
                'overlast_' . $this->faker->uuid() . '.jpg',
                'overlast_' . $this->faker->uuid() . '.jpg',
            ],
        ]);
    }
}
