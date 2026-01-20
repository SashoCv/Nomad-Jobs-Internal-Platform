<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyAdress;
use App\Models\CompanyEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class CompanyService
{
    /**
     * Create a new company with related data.
     */
    public function createCompany(array $companyData, Request $request): Company
    {
        return DB::transaction(function () use ($companyData, $request) {
            


            $company = Company::create($companyData);

            $this->handleFileUpload($request, $company, 'companyLogo', 'logoPath', 'logoName');
            $this->handleFileUpload($request, $company, 'companyStamp', 'stampPath', 'stampName');

            $company->save();

            $companyAddresses = json_decode($request->company_addresses, true);
            if ($companyAddresses) {
                $this->handleCompanyAddresses($company, $companyAddresses);
            }

            $companyEmails = json_decode($request->emails, true);
            if ($companyEmails) {
                $this->handleCompanyEmails($company, $companyEmails);
            }

            return $company;
        });
    }

    /**
     * Update an existing company.
     */
    public function updateCompany(Company $company, array $updateData, Request $request): Company
    {
        return DB::transaction(function () use ($company, $updateData, $request) {
            

            
            // Handle employedByMonths specially as it might be in the request but not in updateData if passed separately
            if ($request->employedByMonths && $request->employedByMonths !== 'null') {
                $updateData['employedByMonths'] = json_decode($request->employedByMonths, true);
            } else {
                // If explicitly set to null/empty in request, we might want to clear it, 
                // but controller logic was: if param exists, decode it.
                // The controller passed this in $updateData. We should ensure it's handled.
            }

            $company->fill($updateData);

            $this->handleFileUpload($request, $company, 'companyLogo', 'logoPath', 'logoName');
            $this->handleFileUpload($request, $company, 'companyStamp', 'stampPath', 'stampName');

            $company->save();

            if ($request->company_addresses) {
                $companyAddresses = json_decode($request->company_addresses, true);
                if ($companyAddresses) {
                    $this->handleCompanyAddresses($company, $companyAddresses, true);
                }
            }

            if ($request->emails) {
                $companyEmails = json_decode($request->emails, true);
                if ($companyEmails) {
                    $this->handleCompanyEmails($company, $companyEmails, true);
                }
            }

            return $company;
        });
    }

    /**
     * Delete a company.
     */
    public function deleteCompany(Company $company): bool
    {
        return $company->delete();
    }



    /**
     * Handle file upload for company.
     */
    private function handleFileUpload(Request $request, Company $company, string $fileField, string $pathField, string $nameField): void
    {
        if ($request->hasFile($fileField)) {
            $path = Storage::disk('public')->put('companyImages', $request->file($fileField));
            $company->$pathField = $path;
            $company->$nameField = $request->file($fileField)->getClientOriginalName();
        }
    }

    /**
     * Handle company addresses creation/update.
     */
    private function handleCompanyAddresses(Company $company, array $addresses, bool $isUpdate = false): void
    {
        if ($isUpdate) {
            CompanyAdress::where('company_id', $company->id)->delete();
        }

        foreach ($addresses as $address) {
            // Handle city field - if it's an array/object, extract name or use city_id
            $cityValue = null;
            if (isset($address['city'])) {
                if (is_array($address['city']) && isset($address['city']['name'])) {
                    $cityValue = $address['city']['name'];
                } elseif (is_string($address['city'])) {
                    $cityValue = $address['city'];
                }
            }

            CompanyAdress::create([
                'company_id' => $company->id,
                'address' => $address['address'],
                'city' => $cityValue,
                'state' => $address['state'],
                'zip_code' => $address['zip_code'],
                'city_id' => $address['city_id'] ?? null
            ]);
        }
    }

    /**
     * Handle company emails creation/update.
     * Ensures only one email is marked as default.
     */
    private function handleCompanyEmails(Company $company, array $emails, bool $isUpdate = false): void
    {
        if ($isUpdate) {
            CompanyEmail::where('company_id', $company->id)->delete();
        }

        $hasDefault = false;
        $validEmails = [];
        
        // First pass: validate and check for default
        foreach ($emails as $index => $emailData) {
            $email = $emailData['email'] ?? '';
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $isDefault = filter_var($emailData['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN);
                // Only one can be default
                if ($isDefault && $hasDefault) {
                    $isDefault = false;
                }
                if ($isDefault) {
                    $hasDefault = true;
                }
                $validEmails[] = [
                    'email' => $email,
                    'is_default' => $isDefault,
                    'is_notification_recipient' => filter_var($emailData['is_notification_recipient'] ?? false, FILTER_VALIDATE_BOOLEAN)
                ];
            }
        }
        
        // If no default was set, make the first one default
        if (!$hasDefault && count($validEmails) > 0) {
            $validEmails[0]['is_default'] = true;
        }
        
        // Create records
        foreach ($validEmails as $emailData) {
            CompanyEmail::create([
                'company_id' => $company->id,
                'email' => $emailData['email'],
                'is_default' => $emailData['is_default'],
                'is_notification_recipient' => $emailData['is_notification_recipient']
            ]);
        }
    }
}
