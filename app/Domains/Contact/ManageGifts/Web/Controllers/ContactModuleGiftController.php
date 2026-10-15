<?php

namespace App\Domains\Contact\ManageGifts\Web\Controllers;

use App\Domains\Contact\ManageGifts\Services\CreateGift;
use App\Domains\Contact\ManageGifts\Services\DestroyGift;
use App\Domains\Contact\ManageGifts\Services\UpdateGift;
use App\Domains\Contact\ManageGifts\Web\ViewHelpers\ModuleGiftsViewHelper;
use App\Helpers\GiftHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Gift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactModuleGiftController extends Controller
{
    public function store(Request $request, string $vaultId, string $contactId): JsonResponse
    {
        $gift = (new CreateGift)->execute($this->data($request, $vaultId, $contactId));

        return response()->json([
            'data' => $this->dto($gift, $contactId),
        ], 201);
    }

    public function update(Request $request, string $vaultId, string $contactId, int $giftId): JsonResponse
    {
        $gift = (new UpdateGift)->execute($this->data($request, $vaultId, $contactId) + [
            'gift_id' => $giftId,
        ]);

        return response()->json([
            'data' => $this->dto($gift, $contactId),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, string $contactId, int $giftId): JsonResponse
    {
        (new DestroyGift)->execute([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'gift_id' => $giftId,
        ]);

        return response()->json([
            'data' => true,
        ], 200);
    }

    private function data(Request $request, string $vaultId, string $contactId): array
    {
        return [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'gift_occasion_id' => $request->input('gift_occasion_id'),
            'gift_state_id' => $request->input('gift_state_id'),
            'currency_id' => $request->input('currency_id'),
            'estimated_price' => $request->input('estimated_price'),
            'url' => $request->input('url'),
            'given_at' => $request->input('given_at'),
            'recipient_ids' => collect($request->input('recipients', []))->pluck('id')->filter()->values()->all(),
        ];
    }

    private function dto(Gift $gift, string $contactId): array
    {
        $contact = Contact::findOrFail($contactId);
        $gift->load(['giftOccasion', 'giftState', 'currency', 'recipients']);

        return ModuleGiftsViewHelper::dto(
            $gift,
            $contact,
            Auth::user(),
            GiftHelper::finalGiftStateId($contact->vault->account)
        );
    }
}
