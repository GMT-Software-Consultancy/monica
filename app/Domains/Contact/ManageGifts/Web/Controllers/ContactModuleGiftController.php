<?php

namespace App\Domains\Contact\ManageGifts\Web\Controllers;

use App\Domains\Contact\ManageGifts\Services\CreateGift;
use App\Domains\Contact\ManageGifts\Services\DestroyGift;
use App\Domains\Contact\ManageGifts\Services\UpdateGift;
use App\Domains\Contact\ManageGifts\Web\ViewHelpers\ModuleGiftViewHelper;
use App\Helpers\MonetaryNumberHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactModuleGiftController extends Controller
{
    public function store(Request $request, string $vaultId, string $contactId)
    {
        $data = array_merge([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
        ], $this->giftData($request));

        $gift = (new CreateGift)->execute($data);

        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleGiftViewHelper::dtoGift($gift->refresh(), $contact, Auth::user()),
        ], 201);
    }

    public function update(Request $request, string $vaultId, string $contactId, int $giftId)
    {
        $data = array_merge([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'gift_id' => $giftId,
        ], $this->giftData($request));

        $gift = (new UpdateGift)->execute($data);

        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleGiftViewHelper::dtoGift($gift->refresh(), $contact, Auth::user()),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, string $contactId, int $giftId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'gift_id' => $giftId,
        ];

        (new DestroyGift)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }

    private function giftData(Request $request): array
    {
        $currency = $request->input('currency_id') ? Currency::find($request->input('currency_id')) : null;
        $price = $request->input('estimated_price');

        return [
            'gift_occasion_id' => $request->input('gift_occasion_id') ?: null,
            'gift_state_id' => $request->input('gift_state_id') ?: null,
            'currency_id' => $request->input('currency_id') ?: null,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'url' => $request->input('link') ?: null,
            'estimated_price' => $price !== null && $price !== '' ? MonetaryNumberHelper::parseInput((string) $price, optional($currency)->code) : null,
            'given_at' => $request->input('given_at') ?: null,
            'recipient_ids' => collect($request->input('recipients'))->pluck('id')->filter()->values()->all(),
        ];
    }
}
