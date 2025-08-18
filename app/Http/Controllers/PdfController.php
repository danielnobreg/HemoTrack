<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;
use Carbon\Carbon;

class PdfController extends Controller
{
    // Define a ordem de exibição dos resultados
    private array $ORDER = [
        'Hemacias', 'Hb', 'Ht', 'VCM', 'HCM', 'CHCM', 'RDW', 'Leucograma', 'Plaquetas', 'Ur', 'Cr', 'TGO', 'TGP', 'FA', 'GGT',
        'PT', 'Na', 'BT', 'K', 'Lipase', 'Amilase'
    ];

    // Mapa de exames evoluído com mais palavras-chave baseadas nos seus exemplos
    private array $EXAMES_MAP = [
        'Hemacias'   => ['Hemácias', 'Hemacias'],
        'Hb'         => ['Hemoglobina'],
        'Ht'         => ['Hematócrito', 'Hematocrito'],
        'VCM'        => ['VCM'],
        'HCM'        => ['HCM'],
        'CHCM'       => ['CHCM'],
        'RDW'        => ['RDW'],
        'Leucograma' => ['LEUCOGRAMA', 'Leucócitos', 'Contagem Global'],
        'Plaquetas'  => ['CONTAGEM DE PLAQUETAS', 'Plaquetas'],
        'Ur'         => ['UREIA', 'URÉIA SÉRICA'],
        'Cr'         => ['CREATININA', 'CREATININA SÉRICA'],
        'TGO'        => ['ASPARTATO AMINOTRANSFERASE', 'AST/TGO', 'TGO'],
        'TGP'        => ['ALANINA AMINOTRANSFERASE', 'ALT/TGP', 'TGP'],
        'FA'         => ['FOSFATASE ALCALINA'],
        'GGT'        => ['GAMA GLUTAMIL TRANSFERASE'],
        'PT'         => ['PROTEINAS TOTAIS'],
        'Alb'        => ['ALBUMINA'],
        'Glob'       => ['GLOBULINA'],
        'Na'         => ['SODIO', 'SÓDIO SÉRICO'],
        'BT'         => ['BILIRRUBINAS TOTAIS'],
        'BD'         => ['BILIRRUBINA DIRETA'],
        'BI'         => ['BILIRRUBINA INDIRETA'],
        'K'          => ['POTASSIO', 'POTÁSSIO SÉRICO'],
        'Lipase'     => ['LIPASE'],
        'Amilase'    => ['AMILASE'],
    ];


    public function index()
    {
        return view('welcome');
    }

    public function upload(Request $request)
    {
        $request->validate(['pdf' => 'required|file|mimes:pdf|max:10240']);

        // --- Leitura do PDF ---
        $file = $request->file('pdf');
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($file->getRealPath());
            $texto = $pdf->getText();
        } catch (\Exception $e) {
            return back()->withErrors(['pdf' => 'Erro ao processar o PDF.']);
        }

        $linhas = preg_split("/\r\n|\n|\r/", $texto);

        // --- Extração dos Dados ---
        $resultados = $this->extrairResultados($linhas);

        // --- Formatação da Saída ---
        $data = $this->extrairData($texto);
        $saida = $this->formatarSaida($data, $resultados);

        return view('welcome', ['output' => $saida, 'raw' => $texto]);
    }

    /**
     * Extrai os resultados dos exames com uma lógica mais robusta.
     */
    private function extrairResultados(array $linhas): array
    {
        $encontrados = [];
        $numLinhas = count($linhas);

        for ($i = 0; $i < $numLinhas; $i++) {
            $linhaAtual = trim($linhas[$i]);
            if ($linhaAtual === '') continue;

            foreach ($this->EXAMES_MAP as $sigla => $keywords) {
                if (isset($encontrados[$sigla])) continue;

                foreach ($keywords as $keyword) {
                    // Padrão 1: Tenta encontrar keyword e valor na MESMA linha
                    $patternMesmaLinha = '/\b' . preg_quote($keyword, '/') . '\b[^\d\n]*([\d.,]+)/i';
                    if (preg_match($patternMesmaLinha, $linhaAtual, $matches)) {
                        $encontrados[$sigla] = $this->normalizarValor($matches[1]);
                        continue 3; // Pula para a próxima linha do PDF
                    }

                    // Padrão 2: Tenta encontrar keyword numa linha e o valor na PRÓXIMA
                    $patternSoKeyword = '/^\s*' . preg_quote($keyword, '/') . '\b/i';
                    if (preg_match($patternSoKeyword, $linhaAtual) && ($i + 1 < $numLinhas)) {
                        $proximaLinha = trim($linhas[$i + 1]);
                        // Procura por um número no início da próxima linha
                        if (preg_match('/^([\d.,]+)/', $proximaLinha, $matches)) {
                            $encontrados[$sigla] = $this->normalizarValor($matches[1]);
                            $i++; // Pula a próxima linha, pois já a processamos
                            continue 3; // Pula para a próxima linha do PDF
                        }
                    }
                }
            }
        }
        return $encontrados;
    }

    /**
     * Converte uma string de valor (ex: "1.234,56") para um float padronizado.
     */
    private function normalizarValor(string $valorStr): float
    {
        $valorSemMilhar = str_replace('.', '', $valorStr);
        $valorPadronizado = str_replace(',', '.', $valorSemMilhar);
        return (float)$valorPadronizado;
    }

    private function extrairData(string $texto): string
    {
        if (preg_match('/(\d{2}[.\-\/]\d{2}[.\-\/]\d{2,4})/', $texto, $m)) {
            try {
                return Carbon::parse(str_replace(['/', '-'], '.', $m[1]))->format('d.m.Y');
            } catch (\Exception $e) {}
        }
        return now()->format('d.m.Y');
    }

    private function formatarSaida(string $data, array $resultados): string
    {
        if (empty($resultados)) {
            return 'Lab '.$data.': Sem resultados reconhecidos.';
        }

        $saidaFormatada = [];
        foreach ($this->ORDER as $sigla) {
            if (isset($resultados[$sigla])) {
                $valor = $resultados[$sigla];
                if ($sigla === 'PT' && isset($resultados['Alb']) && isset($resultados['Glob'])) {
                    $saidaFormatada[] = sprintf('PT %.2f (alb %.1f | Glob %.2f)', $valor, $resultados['Alb'], $resultados['Glob']);
                } elseif ($sigla === 'BT' && isset($resultados['BD']) && isset($resultados['BI'])) {
                    $saidaFormatada[] = sprintf('BT %.2f (BD %.2f | BI %.2f)', $valor, $resultados['BD'], $resultados['BI']);
                } else if (!in_array($sigla, ['Alb', 'Glob', 'BD', 'BI'])) {
                    if ($valor >= 1000) {
                        $saidaFormatada[] = $sigla . ' ' . number_format($valor, 0, ',', '.');
                    } else {
                        $saidaFormatada[] = $sigla . ' ' . str_replace('.', ',', $valor);
                    }
                }
            }
        }

        return 'Lab '.$data.': '.implode('; ', $saidaFormatada).'.';
    }
}