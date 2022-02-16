<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\Shared\ProcessTestingTrait;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenVersion;
use ProcessMaker\Models\User;
use ProcessMaker\Repositories\BpmnDocument;
use ProcessMaker\SanitizeHelper;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;

class SanitizeHelperTest extends TestCase
{
    use ProcessTestingTrait, WithFaker, RequestHelper, ResourceAssertionsTrait;

    protected $screen, $screenVersion, $process, $processRequest;

    protected function withUserSetup()
    {
        $this->user->is_administrator = true;
        $this->user->status = 'ACTIVE';
        $this->user->save();
    }

    public function testSingleRichTextSanitization()
    {
        // Prepare scenario ..
        $this->createScreen('tests/Fixtures/sanitize_single_rich_text_screen.json');
        $this->createProcess('tests/Fixtures/sanitize_single_task.bpmn');
        $this->createProcessRequest();
        $task = $this->createTask($this->process->id, $this->screenVersion->id, $this->processRequest->id, 'node_9');
        $data['data'] = $this->dataSingleTask();

        // Call api and do sanitization ..
        $route = route('api.tasks.update', [$task->id, 'status' => 'COMPLETED']);
        $response = $this->apiCall('PUT', $route, ['data' => $data]);
        $this->assertStatus(200, $response);

        // Assert do_not_sanitize was updated successfully ..
        $response->assertJsonFragment([
            'do_not_sanitize' => ['form_text_area_1'],
        ]);

        // Assert data was sanitized or not if rich text ..
        $processRequest = ProcessRequest::findOrFail($this->processRequest->id)['data']['data'];

        $this->assertEquals('<p><strong>Do not sanitize<\/strong><\/p>', $processRequest['data']['form_text_area_1']);
        $this->assertEquals('Sanitize', $processRequest['data']['input_1']);
    }

    public function testRichTextSanitizationInsideNestedScreen()
    {
        // Prepare scenario ..
        $this->createScreen('tests/Fixtures/sanitize_single_rich_text_screen.json');
        $childScreen = $this->screen;
        $this->createScreen('tests/Fixtures/sanitize_single_rich_text_nested_screen.json');
        // Configure childId screen to nested screen
        $config = str_replace('"screen":1', '"screen":' . $childScreen->id, json_encode($this->screen->config));
        $this->screen->config = json_decode($config);
        $this->screen->save();
        $this->createProcess('tests/Fixtures/sanitize_single_task_nested.bpmn');
        $this->createProcessRequest();
        $task = $this->createTask($this->process->id, $this->screenVersion->id, $this->processRequest->id, 'node_3');
        $data['data'] = $this->dataSingleTask();

        // Call api and do sanitization ..
        $route = route('api.tasks.update', [$task->id, 'status' => 'COMPLETED']);
        $response = $this->apiCall('PUT', $route, ['data' => $data]);
        $this->assertStatus(200, $response);

        // Assert do_not_sanitize was updated successfully ..
        $response->assertJsonFragment([
            'do_not_sanitize' => ['form_text_area_1'],
        ]);

        // Assert data was sanitized or not if rich text ..
        $processRequest = ProcessRequest::findOrFail($this->processRequest->id)['data']['data'];

        $this->assertEquals('<p><strong>Do not sanitize<\/strong><\/p>', $processRequest['data']['form_text_area_1']);
        $this->assertEquals('Sanitize', $processRequest['data']['input_1']);
    }

    public function testSingleRichTextSanitizationInsideLoop()
    {
        // Prepare scenario ..
        $this->createScreen('tests/Fixtures/sanitize_single_rich_text_inside_loop_screen.json');
        $this->createProcess('tests/Fixtures/sanitize_single_task_loop.bpmn');
        $this->createProcessRequest();
        $task = $this->createTask($this->process->id, $this->screenVersion->id, $this->processRequest->id, 'node_2');
        $data['data'] = $this->dataSingleTaskLoop();

        // Call api and do sanitization ..
        $route = route('api.tasks.update', [$task->id, 'status' => 'COMPLETED']);
        $response = $this->apiCall('PUT', $route, ['data' => $data]);
        $this->assertStatus(200, $response);

        // Assert do_not_sanitize was updated successfully ..
        $response->assertJsonFragment([
            'do_not_sanitize' => ['form_text_area_3'],
        ]);

        // Assert data was sanitized or not if rich text ..
        $processRequest = ProcessRequest::findOrFail($this->processRequest->id)['data']['data'];

        $this->assertEquals('<p><strong>Do not sanitize<\/strong><\/p>', $processRequest['data']['loop_1'][0]['form_text_area_3']);
        $this->assertEquals('Sanitize', $processRequest['data']['loop_1'][0]['form_input_1']);
    }

    public function testRichTextSanitizationInsideLoopInsideNestedScreen()
    {
        // Prepare scenario ..
        $this->createScreen('tests/Fixtures/sanitize_single_rich_text_nested_loop_child_screen.json');
        $childScreen = $this->screen;
        $this->createScreen('tests/Fixtures/sanitize_single_rich_text_nested_loop_screen.json');
        // Configure childId screen to nested screen
        $config = str_replace('"screen":1', '"screen":' . $childScreen->id, json_encode($this->screen->config));
        $this->screen->config = json_decode($config);
        $this->screen->save();
        $this->createProcess('tests/Fixtures/sanitize_single_task_nested_loop.bpmn');
        $this->createProcessRequest();
        $task = $this->createTask($this->process->id, $this->screenVersion->id, $this->processRequest->id, 'node_2');
        $data['data'] = $this->dataSingleTaskNestedLoop();

        // Call api and do sanitization ..
        $route = route('api.tasks.update', [$task->id, 'status' => 'COMPLETED']);
        $response = $this->apiCall('PUT', $route, ['data' => $data]);
        $this->assertStatus(200, $response);

        // Assert do_not_sanitize was updated successfully ..
        $response->assertJsonFragment([
            'do_not_sanitize' => ['form_text_area_4'],
        ]);

        // Assert data was sanitized or not if rich text ..
        $processRequest = ProcessRequest::findOrFail($this->processRequest->id)['data']['data'];

        $this->assertEquals('<p><strong>Do not sanitize<\/strong><\/p>', $processRequest['data']['loop_1'][0]['form_text_area_4']);
        $this->assertEquals('Sanitize', $processRequest['data']['loop_1'][0]['form_input_1']);
    }

    public function testPreloadExceptionForDifferentScreenSanitization()
    {
        // Prepare scenario ..
        $this->createScreen('tests/Fixtures/sanitize_single_rich_text_screen.json');
        $this->createProcess('tests/Fixtures/sanitize_single_task.bpmn');
        $this->createProcessRequest();
        // Setup do_not_sanitize variable with some form_text_area from previous screen ..
        $this->processRequest->do_not_sanitize = ['form_text_area_20'];
        $this->processRequest->save();
        $task = $this->createTask($this->process->id, $this->screenVersion->id, $this->processRequest->id, 'node_9');
        $data['data'] = $this->dataTwoTask();

        // Call api and do sanitization ..
        $route = route('api.tasks.update', [$task->id, 'status' => 'COMPLETED']);
        $response = $this->apiCall('PUT', $route, ['data' => $data]);
        $this->assertStatus(200, $response);

        // Assert do_not_sanitize was updated successfully ..
        $response->assertJsonFragment([
            'do_not_sanitize' => ['form_text_area_1', 'form_text_area_20'],
        ]);

        // Assert data was sanitized or not if rich text ..
        $processRequest = ProcessRequest::findOrFail($this->processRequest->id)['data']['data'];

        $this->assertEquals('<p><strong>Do not sanitize<\/strong><\/p>', $processRequest['data']['form_text_area_1']);
        $this->assertEquals('<p><strong>Do not sanitize<\/strong><\/p>', $processRequest['data']['form_text_area_20']);
        $this->assertEquals('Sanitize', $processRequest['data']['form_text_area_10']);
        $this->assertEquals('Sanitize', $processRequest['data']['input_1']);
    }

    public function testSingleRichTextTwoPagesSanitization()
    {
        // Prepare scenario ..
        $this->createScreen('tests/Fixtures/sanitize_single_rich_text_two_pages_screen.json');
        $this->createProcess('tests/Fixtures/sanitize_single_task_two_pages.bpmn');
        $this->createProcessRequest();
        $task = $this->createTask($this->process->id, $this->screenVersion->id, $this->processRequest->id, 'node_3');
        $data['data'] = $this->dataSingleTaskTwoPages();

        // Call api and do sanitization ..
        $route = route('api.tasks.update', [$task->id, 'status' => 'COMPLETED']);
        $response = $this->apiCall('PUT', $route, ['data' => $data]);
        $this->assertStatus(200, $response);

        // Assert do_not_sanitize was updated successfully ..
        $response->assertJsonFragment([
            'do_not_sanitize' => ['form_text_area_1', 'form_text_area_2'],
        ]);

        // Assert data was sanitized or not if rich text ..
        $processRequest = ProcessRequest::findOrFail($this->processRequest->id)['data']['data'];

        $this->assertEquals('<p><strong>Do not sanitize page 2<\/strong><\/p>', $processRequest['data']['form_text_area_1']);
        $this->assertEquals('<p><strong>Do not sanitize page 1<\/strong><\/p>', $processRequest['data']['form_text_area_2']);
        $this->assertEquals('Sanitize', $processRequest['data']['input_1']);
    }

    private function createScreen($screenConfigFilePath)
    {
        $this->screen = factory(Screen::class)->create([
            'config' => json_decode(file_get_contents(base_path($screenConfigFilePath)))
        ]);

        $this->screenVersion = factory(ScreenVersion::class)->create([
            'screen_id' => $this->screen->id,
            'type' => 'FORM',
            'config' => $this->screen->config,
            'status' => 'ACTIVE'
        ]);
    }

    private function createProcess($processFilePath)
    {
        $bpmn = file_get_contents(base_path($processFilePath));
        $bpmn = str_replace('pm:screenRef="1"', 'pm:screenRef="' . $this->screen->id .'"', $bpmn);
        $this->process = factory(Process::class)->create([
            'bpmn' => $bpmn,
            'user_id' => $this->user->id,
        ]);
    }

    private function createProcessRequest()
    {
        $this->processRequest = ProcessRequest::create([
            'name' => $this->faker->sentence(3),
            'data' => [],
            'status' => 'ACTIVE',
            'callable_id' => 'ProcessId',
            'user_id' => $this->user->id,
            'process_id' => $this->process->getKey(),
            'process_collaboration_id' => null,
        ]);
    }

    private function createTask($processId, $screenVersionId, $processRequestId, $node)
    {
        $task = factory(ProcessRequestToken::class)->create([
            'process_id' => $processId,
            'version_type' => 'ProcessMaker\Models\ScreenVersion',
            'version_id' => $screenVersionId,
            'element_id' => $node,
            'element_type' => 'task',
            'status' => 'ACTIVE',
            'process_request_id' => $processRequestId
        ]);
        return $task;
    }

    private function dataSingleTask()
    {
        return [
            "status" => "ACTIVE",
            "data" => [
                "_user" => [
                    "id" => 1
                ],
                "_request" => [
                    "id" => 1
                ],
                "form_text_area_1" => "<p><strong>Do not sanitize<\/strong><\/p>",
                "input_1" => "<p><strong>Sanitize<\/strong><\/p>"
            ]
        ];
    }

    private function dataSingleTaskNestedLoop()
    {
       return [
            "status" => "ACTIVE",
            "data" => [
                "_user" => [
                    "id" => 1
                ],
                "_request" => [
                    "id" => 1
                ],
                "loop_1" => [
                    [
                        "form_text_area_4" => "<p><strong>Do not sanitize<\/strong><\/p>",
                        "form_input_1" => "<p><strong>Sanitize<\/strong><\/p>"
                    ]
                ]
            ]
        ];
    }

    private function dataTwoTask()
    {
        return [
            "status" => "ACTIVE",
            "data" => [
                "_user" => [
                    "id" => 1
                ],
                "_request" => [
                    "id" => 1
                ],
                "form_text_area_1" => "<p><strong>Do not sanitize<\/strong><\/p>",
                "form_text_area_20" => "<p><strong>Do not sanitize<\/strong><\/p>",
                "form_text_area_10" => "<p><strong>Sanitize<\/strong><\/p>",
                "input_1" => "<p><strong>Sanitize<\/strong><\/p>"
            ]
        ];
    }

    private function dataSingleTaskLoop()
    {
        return [
            "status" => "ACTIVE",
            "data" => [
                "_user" => [
                    "id" => 1
                ],
                "_request" => [
                    "id" => 1
                ],
                "loop_1" => [
                    [
                        "form_text_area_3" => "<p><strong>Do not sanitize<\/strong><\/p>",
                        "form_input_1" => "<p><strong>Sanitize<\/strong><\/p>"
                    ]
                ]
            ]
        ];
    }

    private function dataSingleTaskTwoPages()
    {
        return [
            "status" => "ACTIVE",
            "data" => [
                "_user" => [
                    "id" => 1
                ],
                "_request" => [
                    "id" => 1
                ],
                "form_text_area_1" => "<p><strong>Do not sanitize page 2<\/strong><\/p>",
                "form_text_area_2" => "<p><strong>Do not sanitize page 1<\/strong><\/p>",
                "input_1" => "<p><strong>Sanitize<\/strong><\/p>"
            ]
        ];
    }
}