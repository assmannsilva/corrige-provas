import type { Exam, Question } from './exam';

export type SubmissionStatus =
    | 'uploaded'
    | 'processing'
    | 'computer_vision_completed'
    | 'grading'
    | 'completed'
    | 'failed';

export type SubmissionAnswerStatus = 'extracted' | 'failed';

export type SubmissionFile = {
    id: number;
    submission_id: number;
    type: string;
    path: string;
    mime_type: string | null;
    order: number;
    created_at: string;
    updated_at: string;
};

export type SubmissionAnswer = {
    id: number;
    submission_id: number;
    question_id: number;
    raw_text: string | null;
    crop_path: string | null;
    score: number | null;
    feedback: string | null;
    needs_review: boolean;
    graded_at: string | null;
    grade_metadata: Record<string, unknown> | null;
    status: SubmissionAnswerStatus;
    metadata: Record<string, unknown> | null;
    question?: Question;
    created_at: string;
    updated_at: string;
};

export type ComputerVisionProcessing = {
    id: string;
    submission_id: number;
    status: string;
    error_message: string | null;
    started_at: string | null;
    completed_at: string | null;
    created_at: string;
    updated_at: string;
};

export type Submission = {
    id: number;
    exam_id: number;
    student_name: string | null;
    student_class: string | null;
    status: SubmissionStatus;
    total_score: number | null;
    submitted_at: string | null;
    graded_at: string | null;
    files?: SubmissionFile[];
    answers?: SubmissionAnswer[];
    computer_vision_processings?: ComputerVisionProcessing[];
    exam?: Exam;
    created_at: string;
    updated_at: string;
};
