<?php

namespace App\DTO;

use Illuminate\Http\Request;

class ClientFilterData
{
    public function __construct(
        public ?string $search = null,
        public ?int $perPage = 25,
        public ?int $page = 1,
        public ?int $sexe = null,
        public ?int $status_familiale = null,
        public ?int $type_document = null,
        public ?int $type_cli = null,
        public ?int $status_cli = null,
        public ?string $sort_colonne = null,
        public ?string $sort_direction = null
    )
    {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            $request->input('search'),
            $request->input('perPage', 25),
            $request->input('page', 1),
            $request->input('sexe'),
            $request->input('status_familiale'),
            $request->input('type_document'),
            $request->input('type_cli'),
            $request->input('status_cli'),
            $request->input('sort_colonne'),
            $request->input('sort_direction')
        );
    }

}
