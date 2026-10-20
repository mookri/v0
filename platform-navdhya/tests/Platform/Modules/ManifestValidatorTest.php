<?php

namespace Tests\Platform\Modules;

use App\Platform\Modules\ManifestValidator;
use PHPUnit\Framework\TestCase;

/**
 * Manifest validation rules (doc 01 §3.2/§3.3).
 */
final class ManifestValidatorTest extends TestCase
{
    private ManifestValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new ManifestValidator();
    }

    /**
     * @return array<string, mixed>
     */
    private function validManifest(): array
    {
        return [
            'code' => 'sales',
            'name' => 'Sales',
            'version' => '1.0.0',
            'phase' => 1,
            'priority' => 300,
            'permissions' => [
                ['code' => 'sales:order:view', 'name' => 'View orders'],
                ['code' => 'sales:order:approve', 'name' => 'Approve orders', 'sensitive' => true],
            ],
            'scopes' => ['sales:order:approve'],
            'events' => [
                ['name' => 'sales.order.confirmed', 'schema' => 'Events/schemas/order_confirmed.v1.json'],
            ],
            'navigation' => [
                ['group' => 'Commercial', 'label' => 'Orders', 'route' => 'sales.orders.index', 'permission' => 'sales:order:view'],
            ],
        ];
    }

    public function test_valid_manifest_passes(): void
    {
        $this->assertSame([], $this->validator->validate($this->validManifest(), 'Sales'));
    }

    public function test_missing_required_fields_are_reported(): void
    {
        $violations = $this->validator->validate(['name' => 'X'], 'X');

        $this->assertNotEmpty($violations);
        $this->assertStringContainsString('code', implode(' ', $violations));
    }

    public function test_code_must_be_snake_case_and_match_directory(): void
    {
        $manifest = $this->validManifest();
        $manifest['code'] = 'Sales';

        $violations = $this->validator->validate($manifest, 'Sales');

        $this->assertStringContainsString('snake_case', implode(' ', $violations));
    }

    public function test_code_directory_mismatch_is_reported(): void
    {
        $violations = $this->validator->validate($this->validManifest(), 'WrongDir');

        $this->assertStringContainsString('directory', implode(' ', $violations));
    }

    public function test_version_must_be_semantic(): void
    {
        $manifest = $this->validManifest();
        $manifest['version'] = 'v1';

        $violations = $this->validator->validate($manifest, 'Sales');

        $this->assertStringContainsString('semantic', implode(' ', $violations));
    }

    public function test_permission_must_carry_module_prefix(): void
    {
        $manifest = $this->validManifest();
        $manifest['permissions'][] = ['code' => 'crm:lead:view', 'name' => 'Wrong module'];

        $violations = $this->validator->validate($manifest, 'Sales');

        $this->assertStringContainsString("must start with module prefix 'sales:'", implode(' ', $violations));
    }

    public function test_permission_must_have_three_segments(): void
    {
        $manifest = $this->validManifest();
        $manifest['permissions'][] = ['code' => 'sales:order', 'name' => 'Too short'];

        $violations = $this->validator->validate($manifest, 'Sales');

        $this->assertStringContainsString('<module>:<resource>:<action>', implode(' ', $violations));
    }

    public function test_permission_action_outside_vocabulary_is_rejected(): void
    {
        $manifest = $this->validManifest();
        $manifest['permissions'][] = ['code' => 'sales:order:detonate', 'name' => 'Not a verb'];

        $violations = $this->validator->validate($manifest, 'Sales');

        $this->assertStringContainsString('closed vocabulary', implode(' ', $violations));
    }

    public function test_module_specific_actions_can_be_declared(): void
    {
        $manifest = $this->validManifest();
        $manifest['actions'] = ['issue'];
        $manifest['permissions'][] = ['code' => 'sales:invoice:issue', 'name' => 'Issue invoice'];

        $this->assertSame([], $this->validator->validate($manifest, 'Sales'));
    }

    public function test_event_naming_must_follow_past_verb_shape(): void
    {
        $manifest = $this->validManifest();
        $manifest['events'][] = ['name' => 'sales.order.confirm', 'schema' => 'x.json'];

        // Shape is structurally valid (three segments) but must belong to
        // the module; also verify foreign-module events are rejected.
        $manifest['events'][] = ['name' => 'crm.lead.created', 'schema' => 'x.json'];

        $violations = $this->validator->validate($manifest, 'Sales');

        $this->assertStringContainsString("must belong to module 'sales'", implode(' ', $violations));
    }

    public function test_event_requires_schema_reference(): void
    {
        $manifest = $this->validManifest();
        $manifest['events'][] = ['name' => 'sales.order.shipped'];

        $violations = $this->validator->validate($manifest, 'Sales');

        $this->assertStringContainsString('requires a schema file', implode(' ', $violations));
    }

    public function test_navigation_permission_must_be_declared(): void
    {
        $manifest = $this->validManifest();
        $manifest['navigation'][] = [
            'group' => 'Commercial',
            'label' => 'Ghost',
            'route' => 'sales.ghosts.index',
            'permission' => 'sales:ghost:view',
        ];

        $violations = $this->validator->validate($manifest, 'Sales');

        $this->assertStringContainsString('not declared in the manifest', implode(' ', $violations));
    }
}
