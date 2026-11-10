<?php

namespace App\Services;

use DateTimeImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class BcbSgsClient
{
    /**
     * @return array<int, array{data: string, value: string}>
     *
     * @throws ConnectionException
     * @throws RequestException
     * @throws UnexpectedValueException
     */
    public function fetchSeries(int $code): array
    {
        $response = Http::acceptJson()
            ->timeout(15)->withoutVerifying()
            ->get("https://api.bcb.gov.br/dados/serie/bcdata.sgs.{$code}/dados", [
                'formato' => 'json',
                'dataInicial' => '01/01/2025',
                'dataFinal' => '01/11/2026',
            ]);

        $response->throw();
        $points = $response->json();

        if (! is_array($points) || $points === []) {
            throw new UnexpectedValueException("Resposta vazia ou inválida do BCB para a série {$code}.");
        }

        return array_map(function ($point) use ($code): array {
            if (
                ! is_array($point)
                || ! isset($point['data'], $point['valor'])
                || ! is_string($point['data'])
                || ! is_numeric($point['valor'])
            ) {
                throw new UnexpectedValueException("Ponto inválido recebido do BCB para a série {$code}.");
            }

            $date = DateTimeImmutable::createFromFormat('!d/m/Y', $point['data']);
            $dateErrors = DateTimeImmutable::getLastErrors();

            if (
                $date === false
                || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
                || $date->format('d/m/Y') !== $point['data']
            ) {
                throw new UnexpectedValueException("Data inválida recebida do BCB para a série {$code}.");
            }

            return [
                'data' => $date->format('Y-m-d'),
                'value' => (string) $point['valor'],
            ];
        }, $points);
    }
}
