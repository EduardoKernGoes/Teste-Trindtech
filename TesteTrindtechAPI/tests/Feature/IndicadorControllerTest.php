<?php

namespace Tests\Feature;

use App\Models\Indicador;
use App\Models\User;
use Database\Seeders\IndicadorSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IndicadorControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(new User);
    }

    public function test_it_lists_catalog_indicators_and_their_values(): void
    {
        $indicador = Indicador::create(['nome' => 'IPCA', 'codigo_sgs' => 433]);
        $indicador->valores()->create(['data_referencia' => '2025-01-01', 'valor' => 0.52]);

        $this->getJson('/api/indicadores')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.nome', 'IPCA')
            ->assertJsonPath('0.codigo_sgs', 433)
            ->assertJsonPath('0.dados.0.data', '01/01/2025');
    }

    public function test_it_can_rename_an_indicator_without_changing_its_sgs_code(): void
    {
        $indicador = Indicador::create(['nome' => 'IPCA', 'codigo_sgs' => 433]);

        $this->putJson('/api/indicadores/433', ['nome' => 'Inflação mensal'])
            ->assertOk()
            ->assertJsonPath('message', 'Nome atualizado com sucesso!');

        $this->assertDatabaseHas('indicadores', [
            'id' => $indicador->id,
            'nome' => 'Inflação mensal',
            'codigo_sgs' => 433,
        ]);
    }

    public function test_it_lists_indicator_filter_options_from_the_catalog(): void
    {
        Indicador::create(['nome' => 'IPCA', 'codigo_sgs' => 433]);
        Indicador::create(['nome' => 'IGP-M', 'codigo_sgs' => 189]);

        $this->getJson('/api/indicadores/opcoes')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.codigo_sgs', 189)
            ->assertJsonPath('1.codigo_sgs', 433);
    }

    public function test_it_returns_catalog_indicators_with_their_filtered_values(): void
    {
        $ipca = Indicador::create(['nome' => 'IPCA ajustado', 'codigo_sgs' => 433]);
        $igpm = Indicador::create(['nome' => 'IGP-M', 'codigo_sgs' => 189]);

        $ipca->valores()->create(['data_referencia' => '2025-01-01', 'valor' => 0.52]);
        $ipca->valores()->create(['data_referencia' => '2025-02-01', 'valor' => 1.31]);
        $igpm->valores()->create(['data_referencia' => '2025-01-01', 'valor' => 0.27]);

        $this->getJson('/api/indicadores/filtrar?codigo_sgs=433&data_inicial=2025-02-01')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.nome', 'IPCA ajustado')
            ->assertJsonPath('0.codigo_sgs', 433)
            ->assertJsonPath('0.dados.0.data', '01/02/2025')
            ->assertJsonPath('0.dados.0.valor', 1.31);
    }

    public function test_deleting_an_indicator_also_deletes_its_values(): void
    {
        $ipca = Indicador::create(['nome' => 'IPCA', 'codigo_sgs' => 433]);
        $igpm = Indicador::create(['nome' => 'IGP-M', 'codigo_sgs' => 189]);

        $ipca->valores()->create(['data_referencia' => '2025-01-01', 'valor' => 0.52]);
        $igpm->valores()->create(['data_referencia' => '2025-01-01', 'valor' => 0.27]);

        $this->deleteJson('/api/indicadores/433')->assertOk();

        $this->assertDatabaseMissing('indicadores', ['codigo_sgs' => 433]);
        $this->assertDatabaseMissing('indicador_valores', ['indicador_id' => $ipca->id]);
        $this->assertDatabaseHas('indicadores', ['codigo_sgs' => 189]);
        $this->assertDatabaseHas('indicador_valores', ['indicador_id' => $igpm->id]);
    }

    public function test_an_indicator_cannot_have_two_values_for_the_same_date(): void
    {
        $indicador = Indicador::create(['nome' => 'IPCA', 'codigo_sgs' => 433]);
        $indicador->valores()->create(['data_referencia' => '2025-01-01', 'valor' => 0.52]);

        $this->expectException(QueryException::class);

        $indicador->valores()->create(['data_referencia' => '2025-01-01', 'valor' => 0.82]);
    }

    public function test_filter_can_use_only_an_end_date(): void
    {
        $indicador = Indicador::create(['nome' => 'IPCA', 'codigo_sgs' => 433]);
        $indicador->valores()->create(['data_referencia' => '2025-01-01', 'valor' => 0.52]);
        $indicador->valores()->create(['data_referencia' => '2025-02-01', 'valor' => 1.31]);

        $this->getJson('/api/indicadores/filtrar?data_final=2025-01-31')
            ->assertOk()
            ->assertJsonPath('0.dados.0.data', '01/01/2025')
            ->assertJsonCount(1, '0.dados');
    }

    public function test_sync_restores_deleted_indicators_and_preserves_existing_custom_names(): void
    {
        $ipca = Indicador::create(['nome' => 'IPCA personalizado', 'codigo_sgs' => 433]);

        Http::fake([
            'api.bcb.gov.br/*' => Http::response([
                ['data' => '01/01/2025', 'valor' => '0.82'],
            ]),
        ]);

        $this->postJson('/api/indicadores/sincronizar')->assertOk();

        $this->assertDatabaseHas('indicadores', [
            'id' => $ipca->id,
            'nome' => 'IPCA personalizado',
            'codigo_sgs' => 433,
        ]);
        $this->assertDatabaseHas('indicador_valores', [
            'indicador_id' => $ipca->id,
            'data_referencia' => '2025-01-01',
            'valor' => '0.82',
        ]);
        $igpm = Indicador::where('codigo_sgs', 189)->firstOrFail();
        $this->assertDatabaseHas('indicadores', [
            'id' => $igpm->id,
            'nome' => 'IGP-M',
            'codigo_sgs' => 189,
        ]);
        $this->assertDatabaseHas('indicador_valores', [
            'indicador_id' => $igpm->id,
            'data_referencia' => '2025-01-01',
            'valor' => '0.82',
        ]);
        Http::assertSentCount(2);
    }

    public function test_sync_restores_both_default_indicators_when_both_were_deleted(): void
    {
        Http::fake([
            'api.bcb.gov.br/*' => Http::response([
                ['data' => '01/01/2025', 'valor' => '0.82'],
            ]),
        ]);

        $this->postJson('/api/indicadores/sincronizar')->assertOk();

        $this->assertDatabaseHas('indicadores', ['nome' => 'IPCA', 'codigo_sgs' => 433]);
        $this->assertDatabaseHas('indicadores', ['nome' => 'IGP-M', 'codigo_sgs' => 189]);
        $this->assertDatabaseCount('indicadores', 2);
        $this->assertDatabaseCount('indicador_valores', 2);
        Http::assertSentCount(2);
    }

    public function test_sync_returns_an_error_and_saves_nothing_when_bcb_fails(): void
    {
        Http::fake([
            'api.bcb.gov.br/*' => Http::response(['message' => 'Unavailable'], 503),
        ]);

        $this->postJson('/api/indicadores/sincronizar')
            ->assertStatus(502)
            ->assertJsonPath(
                'message',
                'Não foi possível sincronizar os indicadores com o Banco Central. Tente novamente.'
            );

        $this->assertDatabaseCount('indicadores', 0);
        $this->assertDatabaseCount('indicador_valores', 0);
        Http::assertSentCount(1);
    }

    public function test_sync_rejects_invalid_bcb_data_without_saving_anything(): void
    {
        Http::fake([
            'api.bcb.gov.br/*' => Http::response([
                ['data' => '31/02/2025', 'valor' => '0.82'],
            ]),
        ]);

        $this->postJson('/api/indicadores/sincronizar')
            ->assertStatus(502)
            ->assertJsonPath(
                'message',
                'Não foi possível sincronizar os indicadores com o Banco Central. Tente novamente.'
            );

        $this->assertDatabaseCount('indicadores', 0);
        $this->assertDatabaseCount('indicador_valores', 0);
    }

    public function test_seeder_creates_catalog_entries_and_values_without_overwriting_custom_names(): void
    {
        $ipca = Indicador::create(['nome' => 'IPCA personalizado', 'codigo_sgs' => 433]);

        Http::fake([
            'api.bcb.gov.br/*' => Http::response([
                ['data' => '01/01/2025', 'valor' => '0.82'],
            ]),
        ]);

        $this->seed(IndicadorSeeder::class);

        $this->assertDatabaseHas('indicadores', [
            'id' => $ipca->id,
            'nome' => 'IPCA personalizado',
            'codigo_sgs' => 433,
        ]);
        $this->assertDatabaseHas('indicadores', [
            'nome' => 'IGP-M',
            'codigo_sgs' => 189,
        ]);
        $this->assertDatabaseCount('indicador_valores', 2);
    }
}
