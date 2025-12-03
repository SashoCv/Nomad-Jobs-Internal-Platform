<?php

namespace App\Services;

use App\Models\AgentCandidate;
use Barryvdh\DomPDF\Facade\Pdf;

class CvGeneratorService
{
    /**
     * Generate CV PDF for an agent candidate
     *
     * @param int $agentCandidateId
     * @return \Barryvdh\DomPDF\PDF
     * @throws \Exception
     */
    public function generateCv(int $agentCandidateId)
    {
        $agentCandidate = AgentCandidate::with([
            'candidate.education',
            'candidate.experience',
            'candidate.position',
            'candidate.company',
            'companyJob.company',
        ])->findOrFail($agentCandidateId);

        $candidate = $agentCandidate->candidate;

        if (!$candidate) {
            throw new \Exception('Candidate not found for this agent candidate');
        }

        $cvData = $this->prepareCvData($candidate, $agentCandidate);

        $pdf = PDF::loadView('pdf.cv', $cvData);

        // Set paper size and orientation
        $pdf->setPaper('A4', 'portrait');

        return $pdf;
    }

    /**
     * Prepare CV data for the view
     *
     * @param \App\Models\Candidate $candidate
     * @param \App\Models\AgentCandidate $agentCandidate
     * @return array
     */
    private function prepareCvData($candidate, $agentCandidate): array
    {
        return [
            'candidate' => $candidate,
            'agentCandidate' => $agentCandidate,
            'personalInfo' => $this->getPersonalInfo($candidate),
            'education' => $candidate->education()->orderBy('start_date', 'desc')->get(),
            'experience' => $candidate->experience()->orderBy('start_date', 'desc')->get(),
            'position' => $candidate->position,
            'companyJob' => $agentCandidate->companyJob,
            'generatedAt' => now()->format('d.m.Y'),
        ];
    }

    /**
     * Get personal information from candidate
     *
     * @param \App\Models\Candidate $candidate
     * @return array
     */
    private function getPersonalInfo($candidate): array
    {
        return [
            'fullName' => $candidate->fullName ?? $candidate->fullNameCyrillic ?? 'N/A',
            'email' => $candidate->email ?? 'N/A',
            'phone' => $candidate->phoneNumber ?? 'N/A',
            'address' => $candidate->address ?? 'N/A',
            'birthday' => $candidate->birthday ?? 'N/A',
            'nationality' => $candidate->nationality ?? 'N/A',
            'gender' => $candidate->gender ?? 'N/A',
            'passport' => $candidate->passport ?? 'N/A',
            'placeOfBirth' => $candidate->placeOfBirth ?? 'N/A',
            'education' => $candidate->education ?? 'N/A',
            'specialty' => $candidate->specialty ?? 'N/A',
            'qualification' => $candidate->qualification ?? 'N/A',
        ];
    }
}
