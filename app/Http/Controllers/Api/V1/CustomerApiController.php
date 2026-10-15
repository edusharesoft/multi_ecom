<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomerApiController extends ApiController
{
    public function index(Request $request)
    {
        $q = $this->branch()->customers()
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$request->q.'%')->orWhere('phone', 'like', '%'.$request->q.'%')->orWhere('email', $request->q)))
            ->orderByDesc('id');

        return $this->paginated($q->paginate($this->perPage()), fn ($c) => self::customer($c));
    }

    public function show(int $id)
    {
        return ['data' => self::customer($this->branch()->customers()->findOrFail($id))];
    }

    public function store(Request $request)
    {
        $b = $this->branch();
        $request->merge(['phone' => preg_replace('/^\+?88/', '', preg_replace('/[^\d+]/', '', (string) $request->phone))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^01[3-9]\d{8}$/', Rule::unique('customers')->where('branch_id', $b->id)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('customers')->where('branch_id', $b->id)],
        ]);
        if (! $b->withinLimit('max_customers')) {
            return response()->json(['message' => 'Customer limit of the plan reached.'], 422);
        }
        $c = $b->customers()->create($data + ['password' => Str::random(24)]);

        return response()->json(['data' => self::customer($c)], 201);
    }
}
