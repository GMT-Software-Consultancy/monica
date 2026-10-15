<?php

namespace App\Domains\Vault\ManageGifts\Web\Controllers;

use App\Domains\Vault\ManageGifts\Web\ViewHelpers\VaultGiftsIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VaultGiftController extends Controller
{
    public function index(Request $request, string $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);

        return Inertia::render('Vault/Gifts/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => VaultGiftsIndexViewHelper::data($vault, Auth::user()),
        ]);
    }
}
