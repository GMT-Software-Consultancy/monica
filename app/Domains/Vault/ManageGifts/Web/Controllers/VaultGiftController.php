<?php

namespace App\Domains\Vault\ManageGifts\Web\Controllers;

use App\Domains\Vault\ManageGifts\Web\ViewHelpers\VaultGiftsIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VaultGiftController extends Controller
{
    public function index(Request $request, string $vaultId): Response
    {
        $vault = Vault::findOrFail($vaultId);

        return Inertia::render('Vault/Gifts/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => VaultGiftsIndexViewHelper::data($vault),
        ]);
    }
}
