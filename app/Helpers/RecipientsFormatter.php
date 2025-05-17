<?php

namespace App\Helpers;

use App\Models\Contact;
use Illuminate\Support\Collection;

class RecipientsFormatter
{
    /**
     * Format a list of contacts into a readable recipient list
     * Example: "Doug, Angela and John"
     * With duplicate first names: "Doug Steinberg, Doug Todd and Angela"
     *
     * @param array|Collection|null $contacts Array or Collection of Contact models
     * @return string Formatted recipient list
     */
    public static function format($contacts): string
    {
        if (!$contacts || (is_array($contacts) && empty($contacts)) || ($contacts instanceof Collection && $contacts->isEmpty())) {
            return '';
        }

        // Convert to collection if it's an array
        if (is_array($contacts)) {
            $contacts = collect($contacts);
        }

        // Get first names and handle property access via __get magic method or direct property
        $firstNameMap = [];
        foreach ($contacts as $contact) {
            // Try both property access methods (direct or via __get)
            $firstName = self::getContactProperty($contact, 'first_name');
            if (!isset($firstNameMap[$firstName])) {
                $firstNameMap[$firstName] = 0;
            }
            $firstNameMap[$firstName]++;
        }
        
        // Map contacts to names (with last names for duplicates)
        $names = [];
        foreach ($contacts as $contact) {
            $firstName = self::getContactProperty($contact, 'first_name');
            
            if ($firstNameMap[$firstName] > 1) {
                // Use full name for contacts with duplicate first names
                $lastName = self::getContactProperty($contact, 'last_name');
                $names[] = $firstName . ' ' . $lastName;
            } else {
                $names[] = $firstName;
            }
        }

        // Format the list based on number of names
        $count = count($names);
        
        if ($count === 0) {
            return '';
        } elseif ($count === 1) {
            return $names[0];
        } elseif ($count === 2) {
            return $names[0] . ' and ' . $names[1];
        } else {
            $lastItem = array_pop($names);
            return implode(', ', $names) . ' and ' . $lastItem;
        }
    }
    
    /**
     * Get a property from a contact object via direct property access or __get magic method
     *
     * @param object $contact Contact model or mock
     * @param string $property Property name
     * @return string Property value
     */
    private static function getContactProperty($contact, string $property): string
    {
        // Try direct property access first
        if (isset($contact->$property)) {
            return $contact->$property;
        }
        
        // Fall back to __get if available
        if (method_exists($contact, '__get')) {
            return $contact->__get($property) ?: '';
        }
        
        // Last resort, try to call the property as a method
        if (method_exists($contact, $property)) {
            return $contact->$property() ?: '';
        }
        
        return '';
    }
}