<?php

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class GuardianRelationshipEvidenceRules
{
    public static function for(array $acceptedTypes): array
    {
        return [
            'documents' => ['required', 'array', 'min:1', 'max:10'],
            'documents.*.document_type' => ['required', 'string', Rule::in($acceptedTypes)],
            'documents.*.document_side' => ['required', 'string', Rule::in(['front', 'back', 'not_applicable'])],
            'documents.*.pairing_key' => ['nullable', 'uuid'],
            'documents.*.file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public static function metadataErrors(array $documents): array
    {
        $errors = [];
        $pairTypes = [];
        $pairSides = [];

        foreach ($documents as $index => $document) {
            if (! is_array($document)) {
                $errors["documents.{$index}"] = 'Each evidence item must be an object with document metadata.';

                continue;
            }

            $side = (string) ($document['document_side'] ?? '');
            $pairingKey = trim((string) ($document['pairing_key'] ?? ''));
            $documentType = (string) ($document['document_type'] ?? '');

            if ($side === 'not_applicable') {
                if ($pairingKey !== '') {
                    $errors["documents.{$index}.pairing_key"] = 'A single-sided document cannot have a front/back pairing key.';
                }

                continue;
            }

            if (! in_array($side, ['front', 'back'], true) || ! Str::isUuid($pairingKey)) {
                $errors["documents.{$index}.pairing_key"] = 'Front and back files require the same valid pairing key.';

                continue;
            }

            if (isset($pairTypes[$pairingKey]) && $pairTypes[$pairingKey] !== $documentType) {
                $errors["documents.{$index}.document_type"] = 'Front and back files in a pair must use the same document type.';
            }

            if (isset($pairSides[$pairingKey][$side])) {
                $errors["documents.{$index}.document_side"] = 'A front/back pair cannot contain the same side twice.';
            }

            $pairTypes[$pairingKey] = $documentType;
            $pairSides[$pairingKey][$side] = true;
        }

        return $errors;
    }
}
