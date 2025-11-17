<?php

namespace App\Services;

use App\Models\OrgTerms;
use App\Models\OrgUser;
use App\Models\Signature;
use Illuminate\Support\Facades\Storage;
use TCPDF;

class TermsPdfService
{
    protected $tcpdf;

    public function __construct()
    {
        // Initialize TCPDF with UTF-8 support for Arabic text
        // P = Portrait, mm = millimeters, A4 = page format, true = Unicode, UTF-8 = encoding, false = no PDFCreator
        $this->tcpdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    }

    /**
     * Generate a PDF document with terms and member signature
     */
    public function generateSignedTermsPdf(OrgUser $orgUser, Signature $signature): string
    {
        // Get the latest terms for the organization
        $terms = OrgTerms::getLatestForOrg($orgUser->org_id);
        
        if (!$terms) {
            throw new \Exception('No terms found for organization ID: ' . $orgUser->org_id);
        }

        // Prepare member variables for template (using single braces)
        $memberVariables = [
            '{member_name}' => $orgUser->fullName,
            '{member_email}' => $orgUser->email ?? 'Not provided',
            '{member_phone}' => $orgUser->fullPhone,
            '{member_dob}' => $orgUser->dob ? \Carbon\Carbon::parse($orgUser->dob)->format('F j, Y') : 'Not provided',
            '{member_gender}' => $this->getGenderText($orgUser->gender),
            '{signature_date}' => now()->format('F j, Y'),
        ];

        // Get rendered content with variables replaced
        $renderedContent = $terms->getRenderedContent($memberVariables);

        // Configure PDF
        $this->configurePdf($terms->title, $orgUser->org->name ?? 'Organization');

        // Add content to PDF
        $this->tcpdf->AddPage();
        
        // Check if content contains Arabic text and set RTL if needed
        if ($this->containsArabicText($renderedContent)) {
            $this->tcpdf->setRTL(true);
        }
        
        $this->tcpdf->writeHTML($renderedContent, true, false, true, false, '');

        // Add signature to PDF
        $this->addSignatureToPdf($signature);

        // Generate filename
        $filename = $this->generatePdfFilename($orgUser, $terms);

        // Save PDF to storage
        $pdfContent = $this->tcpdf->Output('', 'S'); // Get as string
        $path = config('sign-pad.documents_path') . '/' . $filename;
        
        Storage::disk(config('sign-pad.disk_name'))->put($path, $pdfContent, 'public');

        return $filename;
    }

    /**
     * Configure PDF settings
     */
    protected function configurePdf(string $title, string $orgName): void
    {
        // Set document information
        $this->tcpdf->SetCreator('WodWorx Portal');
        $this->tcpdf->SetAuthor($orgName);
        $this->tcpdf->SetTitle($title);
        $this->tcpdf->SetSubject('Membership Agreement');
        $this->tcpdf->SetKeywords('membership, agreement, terms, signature');

        // Set default header data
        $this->tcpdf->SetHeaderData('', 0, $title, $orgName . "\n" . date('F j, Y'));

        // Set header and footer fonts to support Unicode
        $this->tcpdf->setHeaderFont(['dejavusans', '', 10]);
        $this->tcpdf->setFooterFont(['dejavusans', '', 8]);

        // Set default monospaced font
        $this->tcpdf->SetDefaultMonospacedFont('courier');

        // Set margins
        $this->tcpdf->SetMargins(15, 27, 15);
        $this->tcpdf->SetHeaderMargin(5);
        $this->tcpdf->SetFooterMargin(10);

        // Set auto page breaks
        $this->tcpdf->SetAutoPageBreak(true, 25);

        // Set image scale factor
        $this->tcpdf->setImageScale(1.25);

        // Configure language settings for Arabic text support
        $lg = array();
        $lg['a_meta_charset'] = 'UTF-8';
        $lg['a_meta_dir'] = 'rtl';
        $lg['a_meta_language'] = 'ar';
        $this->tcpdf->setLanguageArray($lg);

        // Set font to DejaVu Sans which supports Arabic characters
        $this->tcpdf->SetFont('dejavusans', '', 11);
        
        // Enable font subsetting to reduce file size
        $this->tcpdf->setFontSubsetting(true);
    }

    /**
     * Add signature image to PDF
     */
    protected function addSignatureToPdf(Signature $signature): void
    {
        if (!$signature->filename) {
            return;
        }

        try {
            // Get signature image from storage
            $signatureImagePath = $signature->getSignatureImagePath();
            $signatureImageUrl = $signature->getSignatureImageUrl();
            
            if (!$signatureImageUrl) {
                return;
            }

            // Download the image content from S3
            $imageContent = file_get_contents($signatureImageUrl);
            
            if ($imageContent === false) {
                return;
            }

            // Create a temporary file for the image
            $tempImagePath = tempnam(sys_get_temp_dir(), 'signature_') . '.png';
            file_put_contents($tempImagePath, $imageContent);

            // Add some space before signature
            $this->tcpdf->Ln(10);
            
            // Add signature section header
            $this->tcpdf->SetFont('dejavusans', 'B', 12);
            $this->tcpdf->Cell(0, 10, 'Digital Signature:', 0, 1, 'L');
            
            // Add signature image
            $this->tcpdf->Image($tempImagePath, '', '', 60, 30, 'PNG', '', 'T', false, 300, '', false, false, 1, false, false, false);
            
            // Add signature date
            $this->tcpdf->Ln(5);
            $this->tcpdf->SetFont('dejavusans', '', 10);
            $this->tcpdf->Cell(0, 5, 'Signed on: ' . now()->format('F j, Y \a\t g:i A'), 0, 1, 'L');

            // Clean up temporary file
            unlink($tempImagePath);

        } catch (\Exception $e) {
            // Log error but don't fail the PDF generation
            \Log::error('Failed to add signature to PDF: ' . $e->getMessage());
        }
    }

    /**
     * Generate PDF filename
     */
    protected function generatePdfFilename(OrgUser $orgUser, OrgTerms $terms): string
    {
        $orgName = $orgUser->org->name ?? 'Org';
        $memberName = $orgUser->fullName;
        $date = now()->format('Y-m-d');
        
        // Sanitize filename
        $orgName = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $orgName));
        $memberName = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $memberName));
        
        return "{$orgName}-{$memberName}-Terms-{$date}.pdf";
    }

    /**
     * Get gender text from numeric value
     */
    protected function getGenderText(?int $gender): string
    {
        return match($gender) {
            1 => 'Male',
            2 => 'Female',
            default => 'Not specified',
        };
    }

    /**
     * Check if PDF generation is possible for an OrgUser
     */
    public function canGeneratePdf(OrgUser $orgUser): bool
    {
        // Check if org has terms
        $terms = OrgTerms::getLatestForOrg($orgUser->org_id);
        return $terms !== null;
    }

    /**
     * Get PDF generation status for an OrgUser
     */
    public function getPdfStatus(OrgUser $orgUser): array
    {
        $terms = OrgTerms::getLatestForOrg($orgUser->org_id);
        
        if (!$terms) {
            return [
                'can_generate' => false,
                'reason' => 'No terms found for organization',
                'terms_count' => 0
            ];
        }

        return [
            'can_generate' => true,
            'terms_title' => $terms->title,
            'terms_version' => $terms->version,
            'effective_date' => $terms->effective_date->format('F j, Y')
        ];
    }

    /**
     * Generate a preview PDF without signature (for testing)
     */
    public function generatePreviewPdf(OrgUser $orgUser): string
    {
        // Get the latest terms for the organization
        $terms = OrgTerms::getLatestForOrg($orgUser->org_id);
        
        if (!$terms) {
            throw new \Exception('No terms found for organization ID: ' . $orgUser->org_id);
        }

        // Prepare member variables for template
        $memberVariables = [
            '{{member_name}}' => $orgUser->fullName,
            '{{member_email}}' => $orgUser->email ?? 'Not provided',
            '{{member_phone}}' => $orgUser->phoneCountry . $orgUser->phoneNumber,
            '{{member_dob}}' => $orgUser->dob ? \Carbon\Carbon::parse($orgUser->dob)->format('F j, Y') : 'Not provided',
            '{{member_gender}}' => $this->getGenderText($orgUser->gender),
            '{{signature_date}}' => '[To be signed]',
        ];

        // Get rendered content with variables replaced
        $renderedContent = $terms->getRenderedContent($memberVariables);

        // Configure PDF
        $this->configurePdf($terms->title . ' (Preview)', $orgUser->org->name ?? 'Organization');

        // Add content to PDF
        $this->tcpdf->AddPage();
        
        // Check if content contains Arabic text and set RTL if needed
        if ($this->containsArabicText($renderedContent)) {
            $this->tcpdf->setRTL(true);
        }
        
        $this->tcpdf->writeHTML($renderedContent, true, false, true, false, '');

        // Add placeholder for signature
        $this->tcpdf->Ln(10);
        $this->tcpdf->SetFont('dejavusans', 'B', 12);
        $this->tcpdf->Cell(0, 10, 'Digital Signature:', 0, 1, 'L');
        $this->tcpdf->SetFont('dejavusans', 'I', 10);
        $this->tcpdf->Cell(0, 5, '[Signature will be added after member signs]', 0, 1, 'L');

        // Generate filename
        $filename = 'preview-' . $this->generatePdfFilename($orgUser, $terms);

        // Save PDF to storage
        $pdfContent = $this->tcpdf->Output('', 'S'); // Get as string
        $path = config('sign-pad.documents_path') . '/' . $filename;
        
        Storage::disk(config('sign-pad.disk_name'))->put($path, $pdfContent, 'public');

        return $filename;
    }

    /**
     * Check if text contains Arabic characters
     */
    protected function containsArabicText(string $text): bool
    {
        // Remove HTML tags to check only text content
        $plainText = strip_tags($text);
        
        // Check for Arabic Unicode range (U+0600 to U+06FF)
        return preg_match('/[\x{0600}-\x{06FF}]/u', $plainText) === 1;
    }
}