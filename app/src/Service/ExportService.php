<?php

namespace App\Service;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * Génère un export Excel ou CSV
     */
    public function export(array $data, array $headers, string $filename, string $format = 'xlsx', ?array $totals = null): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Définir les en-têtes
        $columnIndex = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($columnIndex . '1', $header);
            $columnIndex++;
        }

        // Style de l'en-tête (fond vert, texte blanc, gras)
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '00A651'], // Vert Ouma
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        $lastColumn = chr(ord('A') + count($headers) - 1);
        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray($headerStyle);

        // Hauteur de la ligne d'en-tête
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Remplir les données
        $rowIndex = 2;
        foreach ($data as $row) {
            $columnIndex = 'A';
            foreach ($row as $value) {
                $sheet->setCellValue($columnIndex . $rowIndex, $value);
                $columnIndex++;
            }

            // Alterner les couleurs des lignes
            if ($rowIndex % 2 == 0) {
                $sheet->getStyle('A' . $rowIndex . ':' . $lastColumn . $rowIndex)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8F9FA'],
                    ],
                ]);
            }

            $rowIndex++;
        }

        // Ajouter les totaux si fournis
        if ($totals) {
            $rowIndex++; // Ligne vide
            $columnIndex = 'A';
            foreach ($totals as $total) {
                $sheet->setCellValue($columnIndex . $rowIndex, $total);
                $columnIndex++;
            }

            // Style des totaux (gras, fond jaune clair)
            $sheet->getStyle('A' . $rowIndex . ':' . $lastColumn . $rowIndex)->applyFromArray([
                'font' => ['bold' => true, 'size' => 11],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFF9C4'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ]);
        }

        // Bordures sur toutes les cellules de données
        $dataRange = 'A2:' . $lastColumn . ($rowIndex - 1);
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'DDDDDD'],
                ],
            ],
        ]);

        // Auto-ajuster la largeur des colonnes
        foreach (range('A', $lastColumn) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Figer la première ligne (en-tête)
        $sheet->freezePane('A2');

        // Créer la réponse StreamedResponse
        $response = new StreamedResponse();

        if ($format === 'csv') {
            $writer = new Csv($spreadsheet);
            $writer->setDelimiter(';'); // Pour Excel français
            $writer->setEnclosure('"');
            $writer->setUseBOM(true); // UTF-8 BOM pour Excel

            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.csv"');
        } else {
            $writer = new Xlsx($spreadsheet);

            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.xlsx"');
        }

        $response->headers->set('Cache-Control', 'max-age=0');
        $response->headers->set('Pragma', 'public');

        $response->setCallback(function() use ($writer) {
            $writer->save('php://output');
        });

        return $response;
    }

    /**
     * Génère un export multi-onglets (Excel uniquement)
     */
    public function exportMultiSheet(array $sheets, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // Supprimer la feuille par défaut

        foreach ($sheets as $index => $sheetData) {
            $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $sheetData['name']);
            $spreadsheet->addSheet($sheet, $index);

            // En-têtes
            $columnIndex = 'A';
            foreach ($sheetData['headers'] as $header) {
                $sheet->setCellValue($columnIndex . '1', $header);
                $columnIndex++;
            }

            $lastColumn = chr(ord('A') + count($sheetData['headers']) - 1);

            // Style en-tête
            $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '00A651'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ]);

            $sheet->getRowDimension(1)->setRowHeight(25);

            // Données
            $rowIndex = 2;
            foreach ($sheetData['data'] as $row) {
                $columnIndex = 'A';
                foreach ($row as $value) {
                    $sheet->setCellValue($columnIndex . $rowIndex, $value);
                    $columnIndex++;
                }

                // Alterner les couleurs
                if ($rowIndex % 2 == 0) {
                    $sheet->getStyle('A' . $rowIndex . ':' . $lastColumn . $rowIndex)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F8F9FA'],
                        ],
                    ]);
                }

                $rowIndex++;
            }

            // Totaux si fournis
            if (isset($sheetData['totals'])) {
                $rowIndex++;
                $columnIndex = 'A';
                foreach ($sheetData['totals'] as $total) {
                    $sheet->setCellValue($columnIndex . $rowIndex, $total);
                    $columnIndex++;
                }

                $sheet->getStyle('A' . $rowIndex . ':' . $lastColumn . $rowIndex)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFF9C4'],
                    ],
                ]);
            }

            // Bordures
            $dataRange = 'A2:' . $lastColumn . ($rowIndex - 1);
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'DDDDDD'],
                    ],
                ],
            ]);

            // Auto-ajuster colonnes
            foreach (range('A', $lastColumn) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $sheet->freezePane('A2');
        }

        // Activer le premier onglet
        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);

        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');
        $response->headers->set('Pragma', 'public');

        $response->setCallback(function() use ($writer) {
            $writer->save('php://output');
        });

        return $response;
    }

    /**
     * Génère un nom de fichier intelligent avec date et heure
     */
    public function generateFilename(string $prefix): string
    {
        $date = new \DateTime();
        return sprintf(
            '%s_%s',
            $prefix,
            $date->format('Y-m-d_His')
        );
    }

    /**
     * Formate une valeur monétaire pour l'export
     */
    public function formatCurrency(float $amount, string $currency = 'MRU'): string
    {
        return number_format($amount, 2, ',', ' ') . ' ' . $currency;
    }

    /**
     * Formate une date pour l'export
     */
    public function formatDate(?\DateTimeInterface $date, string $format = 'd/m/Y'): string
    {
        return $date ? $date->format($format) : '';
    }
}
