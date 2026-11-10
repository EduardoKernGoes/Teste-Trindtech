<?php

namespace App\Http\Controllers;

use App\Models\Indicador;
use App\Services\BcbSgsClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use UnexpectedValueException;

class IndicadorController extends Controller
{
    public function index()
    {
        return response()->json($this->formatarIndicadores(Indicador::query()));
    }

    public function update(Request $request, $codigo_sgs)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
        ]);

        $indicador = Indicador::where('codigo_sgs', $codigo_sgs)->firstOrFail();
        $indicador->update(['nome' => $validated['nome']]);

        return response()->json(['message' => 'Nome atualizado com sucesso!'], 200);
    }

    public function destroy($codigo_sgs)
    {
        $indicador = Indicador::where('codigo_sgs', $codigo_sgs)->firstOrFail();
        $indicador->delete();

        return response()->json(['message' => 'Excluído com sucesso!'], 200);
    }

    public function opcoesIndicadores()
    {
        return response()->json(
            Indicador::query()
                ->orderBy('codigo_sgs')
                ->get(['nome', 'codigo_sgs'])
        );
    }

    public function filtrar(Request $request)
    {
        $validated = $request->validate([
            'codigo_sgs' => 'sometimes|nullable|integer',
            'data_inicial' => 'sometimes|nullable|date_format:Y-m-d',
            'data_final' => 'sometimes|nullable|date_format:Y-m-d|after_or_equal:data_inicial',
        ]);

        $query = Indicador::query();

        if (isset($validated['codigo_sgs'])) {
            $query->where('codigo_sgs', $validated['codigo_sgs']);
        }

        $dataInicial = $validated['data_inicial'] ?? null;
        $dataFinal = $validated['data_final'] ?? null;

        if ($dataInicial !== null || $dataFinal !== null) {
            $query->whereHas('valores', function (Builder $valores) use ($dataInicial, $dataFinal): void {
                if ($dataInicial !== null) {
                    $valores->where('data_referencia', '>=', $dataInicial);
                }

                if ($dataFinal !== null) {
                    $valores->where('data_referencia', '<=', $dataFinal);
                }
            });
        }

        return response()->json(
            $this->formatarIndicadores($query, $dataInicial, $dataFinal)
        );
    }

    private function formatarIndicadores(
        Builder $query,
        ?string $dataInicial = null,
        ?string $dataFinal = null
    ) {
        return $query
            ->with(['valores' => function (HasMany $valores) use ($dataInicial, $dataFinal): void {
                if ($dataInicial !== null) {
                    $valores->where('data_referencia', '>=', $dataInicial);
                }

                if ($dataFinal !== null) {
                    $valores->where('data_referencia', '<=', $dataFinal);
                }

                $valores->orderBy('data_referencia');
            }])
            ->orderBy('codigo_sgs')
            ->get()
            ->map(function (Indicador $indicador): array {
                return [
                    'nome' => $indicador->nome,
                    'codigo_sgs' => $indicador->codigo_sgs,
                    'dados' => $indicador->valores->map(function ($valor): array {
                        return [
                            'id' => $valor->id,
                            'valor' => $valor->valor,
                            'data' => $valor->data_referencia->format('d/m/Y'),
                        ];
                    })->values(),
                ];
            });
    }

    public function sincronizar(BcbSgsClient $bcb)
    {
        $series = [
            ['nome' => 'IPCA', 'codigo_sgs' => 433],
            ['nome' => 'IGP-M', 'codigo_sgs' => 189],
        ];

        $dadosPorSerie = [];
        $codigoComFalha = null;

        try {
            foreach ($series as $serie) {
                $codigoComFalha = $serie['codigo_sgs'];
                $dadosPorSerie[] = [$serie, $bcb->fetchSeries($serie['codigo_sgs'])];
            }
        } catch (ConnectionException|RequestException|UnexpectedValueException $e) {
            Log::error('Falha ao sincronizar indicador com o BCB.', [
                'codigo_sgs' => $codigoComFalha,
                'exception' => $e,
            ]);

            return response()->json([
                'message' => 'Não foi possível sincronizar os indicadores com o Banco Central. Tente novamente.',
            ], 502);
        }

        DB::transaction(function () use ($dadosPorSerie): void {
            foreach ($dadosPorSerie as [$serie, $dados]) {
                $indicador = Indicador::firstOrCreate(
                    ['codigo_sgs' => $serie['codigo_sgs']],
                    ['nome' => $serie['nome']]
                );

                foreach ($dados as $ponto) {
                    $indicador->valores()->updateOrCreate(
                        ['data_referencia' => $ponto['data']],
                        ['valor' => $ponto['value']]
                    );
                }
            }
        });

        return response()->json(['message' => 'Sincronizado com sucesso!'], 200);
    }
}
