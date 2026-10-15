<?php

namespace Tests\Unit\Domains\Settings\ManageGiftOccasions\Services;

use App\Domains\Settings\ManageGiftOccasions\Services\DestroyGiftOccasion;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Gift;
use App\Models\GiftOccasion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DestroyGiftOccasionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_gift_occasion(): void
    {
        $ross = $this->createAdministrator();
        $giftOccasion = GiftOccasion::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $giftOccasion);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new DestroyGiftOccasion)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $giftOccasion = GiftOccasion::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $account, $giftOccasion);
    }

    /** @test */
    public function it_fails_if_type_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $giftOccasion = GiftOccasion::factory()->create();
        $this->executeService($ross, $ross->account, $giftOccasion);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $giftOccasion = GiftOccasion::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $giftOccasion);
    }

    /** @test */
    public function it_keeps_the_gifts_that_used_the_gift_occasion(): void
    {
        $ross = $this->createAdministrator();
        $giftOccasion = GiftOccasion::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $gift = Gift::factory()->create([
            'gift_occasion_id' => $giftOccasion->id,
            'name' => 'Cookbook',
            'description' => 'A note',
        ]);

        $this->executeService($ross, $ross->account, $giftOccasion);

        $this->assertDatabaseHas('gifts', [
            'id' => $gift->id,
            'gift_occasion_id' => null,
            'name' => 'Cookbook',
            'description' => 'A note',
        ]);
    }

    private function executeService(User $author, Account $account, GiftOccasion $giftOccasion): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'gift_occasion_id' => $giftOccasion->id,
        ];

        (new DestroyGiftOccasion)->execute($request);

        $this->assertDatabaseMissing('gift_occasions', [
            'id' => $giftOccasion->id,
        ]);
    }
}
