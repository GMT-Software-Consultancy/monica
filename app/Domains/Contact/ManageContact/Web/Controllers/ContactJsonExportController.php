<?php

namespace App\Domains\Contact\ManageContact\Web\Controllers;

use App\Domains\Contact\ManageContact\Services\ExportContactAsJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class ContactJsonExportController extends Controller
{
    public function download(Request $request, Vault $vault, Contact $contact)
    {
        $jsonData = $this->exportJson((string) $vault->id, $contact->id);
        $name = Str::of($contact->name)->slug(language: App::getLocale());

        return Redirect::back()->with('flash', [
            'data' => $jsonData,
            'filename' => "$name.json",
        ]);
    }

    /**
     * Get the exported JSON version of the contact.
     */
    protected function exportJson(string $vaultId, string $contactId): string
    {
        $contact = app(ExportContactAsJson::class)->execute([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
        ]);

        return (new ContactResource($contact))->toJson(JSON_PRETTY_PRINT);
    }
}
