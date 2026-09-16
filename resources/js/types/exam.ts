import type { Submission } from './submission';

export type QuestionType = 'essay' | 'single_choice' | 'multiple_choice';

export type ExamStatus = 'draft' | 'published' | 'archived';

export type Question = {
    id: number;
    exam_id: number;
    type: QuestionType;
    statement: string;
    weight: number;
    order: number;
    grading_notes: string | null;
    metadata: Record<string, unknown> | null;
    computer_vision_region: Record<string, unknown> | null;
    rendered_html?: string;
    created_at: string;
    updated_at: string;
};

export type QuestionTypeDefinition = {
    identifier: string;
    label: string;
    icon: string;
    default_metadata: Record<string, unknown>;
};

export type Exam = {
    id: number;
    teacher_id: number;
    title: string;
    instructions: string | null;
    status: ExamStatus;
    total_points: number;
    pdf_path: string | null;
    pdf_generated_at: string | null;
    pdf_ready: boolean;
    questions_count?: number;
    questions?: Question[];
    submissions?: Submission[];
    created_at: string;
    updated_at: string;
};
