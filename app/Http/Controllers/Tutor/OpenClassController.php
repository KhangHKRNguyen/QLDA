<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Concerns\RequiresActiveTutor;
use App\Http\Controllers\Controller;
use App\Services\OpenClassService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpenClassController extends Controller
{
    use RequiresActiveTutor;

    public function index(Request $request, OpenClassService $classes): View
    {
        $this->activeTutor();

        $filters = $request->validate([
            'area' => ['nullable', 'string', 'max:120'],
            'subject' => ['nullable', 'string', 'max:100'],
            'grade_level' => ['nullable', 'string', 'max:50'],
            'learning_format' => ['nullable', 'string', 'max:50'],
        ]);

        return view('tutor.open-classes.index', [
            'classes' => $classes->browse($filters),
            'filters' => $filters,
        ]);
    }

    public function show(int $classId, OpenClassService $classes): View|RedirectResponse
    {
        $this->activeTutor();
        $class = $classes->findForTutor($classId);

        if ($class->status !== 'pending' && ! $class->isLocked()) {
            return redirect()->route('tutor.open-classes')
                ->with('error', 'Lớp này không còn khả dụng.');
        }

        return view('tutor.open-classes.show', [
            'class' => $class,
            'isLocked' => $class->isLocked(),
            'isHeldByCurrentTutor' => (int) $class->locked_by_account_id === (int) auth()->id(),
        ]);
    }

    public function claim(int $classId, OpenClassService $classes): RedirectResponse
    {
        $tutor = $this->activeTutor();

        try {
            $payment = $classes->claim($classId, (int) $tutor->account_id);
        } catch (DomainException $e) {
            return redirect()->route('tutor.open-classes.show', $classId)->with('error', $e->getMessage());
        }

        return redirect()->route('tutor.class-payments.show', $payment->payment_transaction_id)
            ->with('success', 'Đã giữ lớp trong 5 phút. Vui lòng hoàn tất thanh toán trong thời hạn này.');
    }
}
