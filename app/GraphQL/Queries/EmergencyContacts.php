<?php

namespace App\GraphQL\Queries;

use App\Enums\EmergencyContact\CoverageMode;
use App\Models\EmergencyContact;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final class EmergencyContacts
{
    /**
     * Return a value for the field.
     *
     * @param  @param  null  $root Always null, since this field has no parent.
     * @param  array{}  $args The field arguments passed by the client.
     * @param GraphQLContext $context Shared between all fields.
     * @param ResolveInfo $resolveInfo Metadata for advanced query resolution.
     * @return mixed
     */
    public function __invoke($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $emergencyContacts = EmergencyContact::query();

        if (isset($args['name'])) {
            $emergencyContacts->where('name', $args['name']);
        }

        if (isset($args['department_type'])) {
            $emergencyContacts->where('department_type', $args['department_type']);
        }

        if (isset($args['is_active'])) {
            $emergencyContacts->where('is_active', $args['is_active']);
        }

        if (isset($args['hasDistrictEmergencyContacts']) && is_array($args['hasDistrictEmergencyContacts']) && isset($args['hasDistrictEmergencyContacts']['column'])) {
            $hasDistrictEmergencyContacts = $args['hasDistrictEmergencyContacts'];
            unset($args['coverage_mode']);

            $emergencyContacts->where(function ($query) use ($hasDistrictEmergencyContacts) {
                // $query->where('coverage_mode', CoverageMode::NATIONWIDE->value);
                $query->orWhereHas('districtEmergencyContacts', function ($query) use ($hasDistrictEmergencyContacts) {
                    $query->where($hasDistrictEmergencyContacts['column'], $hasDistrictEmergencyContacts['operator'], $hasDistrictEmergencyContacts['value']);
                });
            });

            $emergencyContacts->orderByDesc('coverage_mode');
        }

        return $emergencyContacts->get();
    }
}
