<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * List Contacts
     *
     * Get a paginated list of contacts. You can filter by type and other parameters.
     *
     * @queryParam type string Filter by contact type (lead/customer). Example: lead
     * @queryParam source_id integer Filter by source ID. Example: 1
     * @queryParam status_id integer Filter by status ID. Example: 1
     * @queryParam page integer The page number. Example: 1
     * @queryParam per_page integer Number of items per page. Example: 15
     *
     * @response scenario=success status=200 {
     *   "status": "success",
     *   "data": [
     *     {
     *       "id": 1,
     *       "firstname": "John",
     *       "lastname": "Doe",
     *       "company": "Demo Company",
     *       "type": "lead",
     *       "email": "john@example.com",
     *       "phone": "+1234567890",
     *       "created_at": "2024-02-08 10:00:00",
     *       "updated_at": "2024-02-08 10:00:00"
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "total": 100,
     *     "per_page": 15
     *   }
     * }
     * @response status=401 scenario="unauthenticated" {
     *   "status": "error",
     *   "message": "Invalid API token"
     * }
     */
    public function index(Request $request)
    {
        try {
            $query = Contact::query();

            // Filter by type if provided
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Filter by source if provided
            if ($request->has('source')) {
                $query->where('source', $request->source);
            }

            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $contacts = $query->paginate($request->per_page ?? 15);

            return response()->json([
                'status' => 'success',
                'data'   => $contacts,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => t('failed_to_fetch_contact'),
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create Contact
     *
     * Create a new contact or lead in the system.
     *
     * @bodyParam firstname string required The first name of the contact. Example: John
     * @bodyParam lastname string required The last name of the contact. Example: Doe
     * @bodyParam company string The company name. Example: Acme Corp
     * @bodyParam type string required The contact type (lead/customer). Example: lead
     * @bodyParam email string The contact's email address. Example: john@example.com
     * @bodyParam phone string required The contact's phone number. Example: +1234567890
     * @bodyParam status_id integer required The status ID. Example: 1
     * @bodyParam source_id integer required The source ID. Example: 1
     * @bodyParam country_id integer The country ID. Example: 1
     * @bodyParam description string Any additional notes. Example: Potential client from website
     *
     * @response scenario=success status=201 {
     *   "status": "success",
     *   "message": "Contact created successfully",
     *   "data": {
     *     "id": 1,
     *     "firstname": "John",
     *     "lastname": "Doe",
     *     "email": "john@example.com",
     *     "created_at": "2024-02-08 10:00:00"
     *   }
     * }
     * @response status=422 scenario="validation error" {
     *   "status": "error",
     *   "message": "Validation failed",
     *   "errors": {
     *     "email": ["The email field must be a valid email address."],
     *     "phone": ["The phone field is required."]
     *   }
     * }
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'firstname' => 'required|string|max:255',
                'lastname'  => 'required|string|max:255',
                'email'     => 'required|email|unique:contacts',
                'phone'     => 'required|string|max:20',
                'type'      => 'required|in:lead,contact',
                'source'    => 'nullable|string|max:50',
                'status'    => 'nullable|string|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $contact = Contact::create($request->all());

            return response()->json([
                'status'  => 'success',
                'message' => 'Contact created successfully',
                'data'    => $contact,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create contact',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Contact Details
     *
     * Get detailed information about a specific contact.
     *
     * @urlParam id integer required The ID of the contact. Example: 1
     *
     * @response scenario=success status=200 {
     *   "status": "success",
     *   "data": {
     *     "id": 1,
     *     "firstname": "John",
     *     "lastname": "Doe",
     *     "company": "Demo Company",
     *     "type": "lead",
     *     "email": "john@example.com",
     *     "phone": "+1234567890",
     *     "created_at": "2024-02-08 10:00:00",
     *     "updated_at": "2024-02-08 10:00:00"
     *   }
     * }
     * @response status=404 scenario="not found" {
     *   "status": "error",
     *   "message": "Contact not found"
     * }
     */
    public function show($id)
    {
        try {
            $contact = Contact::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data'   => $contact,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Contact not found',
                'error'   => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update Contact
     *
     * Update an existing contact's information.
     *
     * @urlParam id integer required The ID of the contact. Example: 1
     *
     * @bodyParam firstname string The first name of the contact. Example: John
     * @bodyParam lastname string The last name of the contact. Example: Doe
     * @bodyParam company string The company name. Example: Acme Corp
     * @bodyParam email string The contact's email address. Example: john@example.com
     * @bodyParam phone string The contact's phone number. Example: +1234567890
     * @bodyParam status_id integer The status ID. Example: 1
     *
     * @response scenario=success {
     *   "status": "success",
     *   "message": "Contact updated successfully",
     *   "data": {
     *     "id": 1,
     *     "firstname": "John",
     *     "lastname": "Doe",
     *     "email": "john@example.com",
     *     "updated_at": "2024-02-08 11:00:00"
     *   }
     * }
     */
    public function update(Request $request, $id)
    {
        try {
            $contact = Contact::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'firstname' => 'sometimes|string|max:255',
                'lastname'  => 'sometimes|string|max:255',
                'email'     => 'sometimes|email|unique:contacts,email,' . $id,
                'phone'     => 'sometimes|string|max:20',
                'type'      => 'sometimes|in:lead,contact',
                'source'    => 'nullable|string|max:50',
                'status'    => 'nullable|string|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $contact->update($request->all());

            return response()->json([
                'status'  => 'success',
                'message' => 'Contact updated successfully',
                'data'    => $contact,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update contact',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete Contact
     *
     * Remove a contact from the system.
     *
     * @urlParam id integer required The ID of the contact. Example: 1
     *
     * @response scenario=success {
     *   "status": "success",
     *   "message": "Contact deleted successfully"
     * }
     * @response status=404 {
     *   "status": "error",
     *   "message": "Contact not found"
     * }
     */
    public function destroy($id)
    {
        try {
            $contact = Contact::findOrFail($id);
            $contact->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Contact deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete contact',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
