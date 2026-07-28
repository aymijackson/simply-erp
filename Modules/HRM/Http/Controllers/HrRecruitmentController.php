<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\HRM\Models\HrJobOpening;
use Modules\HRM\Models\HrApplicant;
use Modules\HRM\Models\HrInterview;
use Modules\HRM\Models\Employee;
use App\Models\User;
use Yajra\DataTables\Facades\DataTables;

/**
 * HrRecruitmentController
 *
 * ── PERMISSION MAP ───────────────────────────────────────────────────────────
 * openingIndex, openingDatatable           hrm.recruitment.openings.view
 * openingStore, openingUpdate, openingDestroy, openingPublish
 *                                          hrm.recruitment.openings.manage
 *
 * applicantDatatable                       hrm.recruitment.applicants.view
 * applicantStore, applicantUpdate,
 * applicantStageUpdate, applicantDestroy   hrm.recruitment.applicants.manage
 *
 * interviewStore, interviewUpdate,
 * interviewDestroy                         hrm.recruitment.interviews.manage
 * ────────────────────────────────────────────────────────────────────────────
 */
class HrRecruitmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:hrm.recruitment.openings.view',   ['only' => ['openingIndex','openingDatatable']]);
        $this->middleware('permission:hrm.recruitment.openings.manage', ['only' => ['openingStore','openingUpdate','openingDestroy','openingPublish']]);
        $this->middleware('permission:hrm.recruitment.applicants.view', ['only' => ['applicantDatatable','openingShow']]);
        $this->middleware('permission:hrm.recruitment.applicants.manage',['only' => ['applicantStore','applicantUpdate','applicantStageUpdate','applicantDestroy']]);
        $this->middleware('permission:hrm.recruitment.interviews.manage',['only' => ['interviewStore','interviewUpdate','interviewDestroy']]);
    }

    // ── Job Openings ──────────────────────────────────────────────────────────

    public function openingIndex()
    {
        return view('hrm.recruitment.openings.index');
    }

    public function openingShow(HrJobOpening $hrJobOpening)
    {
        $hrJobOpening->load(['jobPosition','department','applicants.interviews']);
        return view('hrm.recruitment.openings.show', ['opening' => $hrJobOpening]);
    }

    public function openingDatatable(Request $request)
    {
        $q = HrJobOpening::with(['department','jobPosition'])
            ->withCount('applicants')
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->orderByDesc('posted_date');

        return DataTables::eloquent($q)
            ->addColumn('dept_name',     fn($r) => $r->department?->name ?? '—')
            ->addColumn('position_title',fn($r) => $r->jobPosition?->title ?? '—')
            ->addColumn('status_badge',  fn($r) => match($r->status) {
                'open'      => '<span class="badge bg-success">Open</span>',
                'on_hold'   => '<span class="badge bg-warning text-dark">On Hold</span>',
                'closed'    => '<span class="badge bg-secondary">Closed</span>',
                'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
                default     => '<span class="badge bg-light text-dark">'.ucfirst($r->status).'</span>',
            })
            ->addColumn('actions', fn($r) =>
                '<a href="/admin/hrm/recruitment/openings/'.$r->id.'" class="btn btn-xs btn-outline-primary">
                    <i class="fas fa-eye"></i></a>
                 <button class="btn btn-xs btn-warning btn-edit-opening"
                    data-id="'.$r->id.'" data-record="'.e(json_encode($r->toArray())).'">
                    <i class="fas fa-pencil-alt"></i></button>
                 <button class="btn btn-xs btn-danger btn-delete-opening" data-id="'.$r->id.'">
                    <i class="fas fa-trash"></i></button>')
            ->rawColumns(['status_badge','actions'])
            ->make(true);
    }

    public function openingStore(Request $request)
    {
        $v = $request->validate([
            'title'           => ['required','string','max:150'],
            'job_position_id' => ['nullable','integer','exists:hr_job_positions,id'],
            'department_id'   => ['nullable','integer'],
            'vacancies'       => ['nullable','integer','min:1'],
            'description'     => ['nullable','string'],
            'requirements'    => ['nullable','string'],
            'posted_date'     => ['nullable','date'],
            'closing_date'    => ['nullable','date'],
            'status'          => ['required', Rule::in(['draft','open','on_hold','closed','cancelled'])],
        ]);

        $opening = HrJobOpening::create([...$v,'company_id'=>auth()->user()->company_id??1,'created_by'=>auth()->id(),'updated_by'=>auth()->id()]);

        return response()->json(['message'=>'Job opening created.','opening'=>$opening], 201);
    }

    public function openingUpdate(Request $request, HrJobOpening $hrJobOpening)
    {
        $v = $request->validate([
            'title'           => ['required','string','max:150'],
            'job_position_id' => ['nullable','integer','exists:hr_job_positions,id'],
            'department_id'   => ['nullable','integer'],
            'vacancies'       => ['nullable','integer','min:1'],
            'description'     => ['nullable','string'],
            'requirements'    => ['nullable','string'],
            'posted_date'     => ['nullable','date'],
            'closing_date'    => ['nullable','date'],
            'status'          => ['required', Rule::in(['draft','open','on_hold','closed','cancelled'])],
        ]);

        $hrJobOpening->update([...$v,'updated_by'=>auth()->id()]);

        return response()->json(['message'=>'Job opening updated.']);
    }

    public function openingDestroy(HrJobOpening $hrJobOpening)
    {
        $hrJobOpening->delete();
        return response()->json(['message'=>'Opening deleted.']);
    }

    // ── Applicants ────────────────────────────────────────────────────────────

    public function applicantDatatable(HrJobOpening $hrJobOpening)
    {
        $q = HrApplicant::with('interviews')
            ->where('job_opening_id', $hrJobOpening->id)
            ->orderByDesc('created_at');

        return DataTables::eloquent($q)
            ->addColumn('full_name',     fn($r) => $r->full_name)
            ->addColumn('interview_count', fn($r) => $r->interviews->count())
            ->addColumn('stage_badge',   fn($r) => match($r->stage) {
                'hired'    => '<span class="badge bg-success">Hired</span>',
                'rejected' => '<span class="badge bg-danger">Rejected</span>',
                'offer'    => '<span class="badge bg-primary">Offer</span>',
                'interview'=> '<span class="badge bg-info text-dark">Interview</span>',
                'screening'=> '<span class="badge bg-warning text-dark">Screening</span>',
                default    => '<span class="badge bg-light text-dark">'.ucfirst($r->stage).'</span>',
            })
            ->addColumn('rating_stars', fn($r) => $r->rating ? str_repeat('★', $r->rating) : '—')
            ->addColumn('actions', fn($r) =>
                '<button class="btn btn-xs btn-warning btn-edit-applicant"
                    data-id="'.$r->id.'" data-record="'.e(json_encode($r->toArray())).'">
                    <i class="fas fa-pencil-alt"></i></button>
                 <button class="btn btn-xs btn-info btn-schedule-interview" data-applicant-id="'.$r->id.'">
                    <i class="fas fa-calendar-check"></i></button>
                 <button class="btn btn-xs btn-danger btn-delete-applicant" data-id="'.$r->id.'">
                    <i class="fas fa-trash"></i></button>')
            ->rawColumns(['stage_badge','actions'])
            ->make(true);
    }

    public function applicantStore(Request $request, HrJobOpening $hrJobOpening)
    {
        $v = $request->validate([
            'first_name' => ['required','string','max:100'],
            'last_name'  => ['nullable','string','max:100'],
            'email'      => ['required','email','max:255'],
            'phone'      => ['nullable','string','max:50'],
            'source'     => ['required', Rule::in(['direct','referral','linkedin','job_board','agency','other'])],
            'referral_name' => ['nullable','string','max:150'],
            'notes'      => ['nullable','string'],
        ]);

        $applicant = HrApplicant::create([
            ...$v,
            'job_opening_id' => $hrJobOpening->id,
            'stage'          => 'applied',
            'created_by'     => auth()->id(),
            'updated_by'     => auth()->id(),
        ]);

        return response()->json(['message'=>'Applicant added.','applicant'=>$applicant], 201);
    }

    public function applicantUpdate(Request $request, HrApplicant $hrApplicant)
    {
        $v = $request->validate([
            'first_name' => ['required','string','max:100'],
            'last_name'  => ['nullable','string','max:100'],
            'email'      => ['required','email','max:255'],
            'phone'      => ['nullable','string','max:50'],
            'source'     => ['required', Rule::in(['direct','referral','linkedin','job_board','agency','other'])],
            'stage'      => ['required', Rule::in(['applied','screening','interview','offer','hired','rejected','withdrawn'])],
            'rating'     => ['nullable','integer','min:1','max:5'],
            'notes'      => ['nullable','string'],
        ]);

        $hrApplicant->update([...$v,'updated_by'=>auth()->id()]);

        return response()->json(['message'=>'Applicant updated.']);
    }

    public function applicantDestroy(HrApplicant $hrApplicant)
    {
        $hrApplicant->delete();
        return response()->json(['message'=>'Applicant deleted.']);
    }

    // ── Interviews ────────────────────────────────────────────────────────────

    public function interviewStore(Request $request, HrApplicant $hrApplicant)
    {
        $v = $request->validate([
            'interviewer_id' => ['required','integer','exists:users,id'],
            'scheduled_at'   => ['required','date'],
            'type'           => ['required', Rule::in(['phone','video','in_person','panel','technical'])],
            'feedback'       => ['nullable','string'],
        ]);

        $interview = HrInterview::create([
            ...$v,
            'applicant_id' => $hrApplicant->id,
            'outcome'      => 'pending',
            'created_by'   => auth()->id(),
            'updated_by'   => auth()->id(),
        ]);

        // Advance applicant stage
        if ($hrApplicant->stage === 'applied' || $hrApplicant->stage === 'screening') {
            $hrApplicant->update(['stage' => 'interview']);
        }

        return response()->json(['message'=>'Interview scheduled.','interview'=>$interview], 201);
    }

    public function interviewUpdate(Request $request, HrInterview $hrInterview)
    {
        $v = $request->validate([
            'scheduled_at'   => ['required','date'],
            'type'           => ['required', Rule::in(['phone','video','in_person','panel','technical'])],
            'outcome'        => ['required', Rule::in(['pending','passed','failed','no_show','rescheduled'])],
            'score'          => ['nullable','numeric','min:0','max:100'],
            'feedback'       => ['nullable','string'],
        ]);

        $hrInterview->update([...$v,'updated_by'=>auth()->id()]);

        return response()->json(['message'=>'Interview updated.']);
    }

    public function interviewDestroy(HrInterview $hrInterview)
    {
        $hrInterview->delete();
        return response()->json(['message'=>'Interview deleted.']);
    }
}