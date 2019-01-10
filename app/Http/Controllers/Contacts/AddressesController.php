<?php

namespace App\Http\Controllers\Contacts;

use Illuminate\Http\Request;
use App\Helpers\LocaleHelper;
use App\Models\Contact\Address;
use App\Models\Contact\Contact;
use App\Helpers\CountriesHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Traits\JsonRespondController;
use Illuminate\Support\Facades\Cache;
use App\Services\Contact\Address\CreateAddress;
use App\Services\Contact\Address\UpdateAddress;
use App\Services\Contact\Address\DestroyAddress;

class AddressesController extends Controller
{
    use JsonRespondController;

    /**
     * Get all the addresses for this contact.
     */
    public function index(Contact $contact)
    {
        $addresses = collect([]);

        foreach ($contact->addresses as $address) {
            $addresses->push($this->addressObject($address));
        }

        return $addresses;
    }

    /**
     * Get all the countries.
     */
    public function getCountries()
    {
        $key = 'countries.'.LocaleHelper::getLocale();

        $countries = Cache::rememberForever($key, function () {
            return CountriesHelper::getAll();
        });

        return response()->json($countries->all());
    }

    /**
     * Store the address.
     */
    public function store(Request $request, Contact $contact)
    {
        $datas = [
            'account_id' => auth()->user()->account_id,
            'contact_id' => $contact->id,
        ] + $request->only([
            'name',
            'country',
            'street',
            'city',
            'province',
            'postal_code',
            'latitude',
            'longitude',
        ]);

        $address = $this->addressObject((new CreateAddress)->execute($datas));

        return $this->setHTTPStatusCode(201)
                    ->respond($address);
    }

    /**
     * Edit the contact field.
     */
    public function edit(Request $request, Contact $contact, Address $address)
    {
        $datas = [
            'account_id' => auth()->user()->account_id,
            'contact_id' => $contact->id,
            'address_id' => $address->id,
        ] + $request->only([
            'name',
            'country',
            'street',
            'city',
            'province',
            'postal_code',
            'latitude',
            'longitude',
        ]);

        $address = $this->addressObject((new UpdateAddress)->execute($datas));

        return $this->respond($address);
    }

    /**
     * Destroy the address.
     *
     * @param Request $request
     * @param Contact $contact
     * @param Address $address
     * @return void
     */
    public function destroy(Request $request, Contact $contact, Address $address)
    {
        $datas = [
            'account_id' => auth()->user()->account_id,
            'address_id' => $address->id,
        ];

        if ((new DestroyAddress)->execute($datas)) {
            return $this->respondObjectDeleted($address->id);
        }
    }

    private function addressObject($address)
    {
        return [
            'id' => $address->id,
            'name' => $address->name,
            'googleMapAddress' => $address->place->getGoogleMapAddress(),
            'googleMapAddressLatitude' => $address->place->getGoogleMapsAddressWithLatitude(),
            'address' => $address->place->getAddressAsString(),
            'country' => $address->place->country,
            'country_name' => $address->place->country_name,
            'street' => $address->place->street,
            'city' => $address->place->city,
            'province' => $address->place->province,
            'postal_code' => $address->place->postal_code,
            'latitude' => $address->place->latitude,
            'longitude' => $address->place->longitude,
            'edit' => false,
        ];
    }
}
