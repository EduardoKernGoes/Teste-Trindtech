<?php

namespace Database\Seeders;

use App\Models\Indicador;
use App\Services\BcbSgsClient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IndicadorSeeder extends Seeder
{
    public function run(BcbSgsClient $bcb): void
    {
        $series = [
            ['nome' => 'IPCA', 'codigo' => 433],
            ['nome' => 'IGP-M', 'codigo' => 189],
        ];

        $dadosPorSerie = [];

        foreach ($series as $serie) {
            $dadosPorSerie[] = [$serie, $bcb->fetchSeries($serie['codigo'])];
        }

        DB::transaction(function () use ($dadosPorSerie): void {
            foreach ($dadosPorSerie as [$serie, $dados]) {
                $indicador = Indicador::firstOrCreate(
                    ['codigo_sgs' => $serie['codigo']],
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
    }
}
