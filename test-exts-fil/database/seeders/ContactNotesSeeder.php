<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\ContactNote;
use Illuminate\Database\Seeder;

class ContactNotesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $contactIds = Contact::pluck('id')->toArray();  // Get all valid contact IDs

        foreach (range(1, 100) as $index) {
            // Pick a random contact ID from the existing contacts
            $contactId = $contactIds[array_rand($contactIds)];

            ContactNote::create([
                'contact_id'        => $contactId,
                'notes_description' => 'Sample note description for contact ID ' . $contactId,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }
}
