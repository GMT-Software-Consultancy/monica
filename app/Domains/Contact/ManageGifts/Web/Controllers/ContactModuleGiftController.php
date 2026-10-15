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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactModuleGiftController extends Controller
{
    public function store(Request $request, string $vaultId, string $contactId)
    {
        $this->validateForm($request);

        $gift = (new CreateGift)->execute($this->formData($request, $vaultId, $contactId));

        return response()->json([
            'data' => $this->dto($gift, $contactId),
        ], 201);
    }

    public function update(Request $request, string $vaultId, string $contactId, int $giftId)
    {
        $this->validateForm($request);

        $gift = (new UpdateGift)->execute(array_merge($this->formData($request, $vaultId, $contactId), [
            'gift_id' => $giftId,
        ]));

        return response()->json([
            'data' => $this->dto($gift, $contactId),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, string $contactId, int $giftId)
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

    /**
     * Validate the form so each field gets its own message, displayed next to
     * the field.
     */
    private function validateForm(Request $request): void
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'recipients' => 'required|array|min:1',
            'estimated_price' => 'nullable|integer|min:0',
            'url' => 'nullable|url:http,https|max:2048',
            'given_at' => 'nullable|date_format:Y-m-d',
        ], [
            'name.required' => __('Please enter what the gift is.'),
            'recipients.required' => __('Please choose at least one recipient.'),
            'recipients.min' => __('Please choose at least one recipient.'),
            'estimated_price.integer' => __('The price must be a whole amount.'),
            'estimated_price.min' => __('The price must be a whole amount.'),
            'url.url' => __('The link must be a web address.'),
        ]);
    }

    private function formData(Request $request, string $vaultId, string $contactId): array
    {
        return [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'gift_occasion_id' => $request->input('gift_occasion_id') ?: null,
            'gift_state_id' => $request->input('gift_state_id') ?: null,
            'currency_id' => $request->input('currency_id') ?: null,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'estimated_price' => $request->filled('estimated_price') ? (int) $request->input('estimated_price') : null,
            'url' => $request->input('url') ?: null,
            'given_at' => $request->input('given_at') ?: null,
            'recipient_ids' => collect($request->input('recipients'))->pluck('id')->all(),
        ];
    }

    private function dto(Gift $gift, string $contactId): array
    {
        $contact = Contact::find($contactId);

        return ModuleGiftsViewHelper::dto(
            $gift->load(['giftState', 'giftOccasion', 'currency', 'recipients']),
            $contact,
            Auth::user(),
            GiftHelper::givenStage(Auth::user()->account)?->id,
        );
    }
}
