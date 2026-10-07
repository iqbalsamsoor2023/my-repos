<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivationModuleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        $createdAt = new Carbon(data_get($this, 'created_at'));
        $updatedAt = new Carbon(data_get($this, 'updated_at'));

        if ($request->module == 'residence') {
            $data = [
                'id' => data_get($this, 'id'),
                'residence_id' => data_get($this, 'residence_id'),
                'module' => 'residence',
                'module_type' => data_get($this, 'feature_id'),
                'module_type_name' => $this->feature_name,
                'is_active' => data_get($this, 'is_active'),
                'created_at' => $createdAt->format('d-m-Y h:i:s A'),
                'updated_at' => $updatedAt->format('d-m-Y h:i:s A'),
            ];
        } else {
            $data = [
                'id' => data_get($this, 'id'),
                'residence_id' => data_get($this, 'residence_id'),
                'module' => data_get($this, 'module'),
                'module_type' => data_get($this, 'module_type'),
                'module_type_name' => $this->module_type_name,
                'is_active' => data_get($this, 'is_active'),
                'created_at' => $createdAt->format('d-m-Y h:i:s A'),
                'updated_at' => $updatedAt->format('d-m-Y h:i:s A'),
            ];
        }

        return $data;
    }
}
