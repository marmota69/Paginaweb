<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseAccessController extends Controller
{
    /**
     * Record the access as a download, then hand the visitor to the course
     * platform. Without a link there is nowhere to go, so show the course page.
     */
    public function __invoke(Request $request, Course $course): RedirectResponse
    {
        abort_unless($course->is_published, 404);

        $course->downloads()->create(['session_id' => $request->session()->getId()]);

        return $course->link
            ? redirect()->away($course->link)
            : redirect()->route('courses.show', $course);
    }
}
