type Submission = {
    id: number;
    status: string;
    review_notes: string | null;
    submitted_at: string | null;
    decided_at: string | null;
};

export type StudioNovel = {
    id: number;
    title: string;
    slug: string;
    cover_path: string | null;
    status: string;
    story_status: string;
    chapter_count: number;
    draft_count: number;
    genres: string[];
    latest_submission: Submission | null;
    updated_at: string | null;
};

export type StudioIndexProps = {
    novels: StudioNovel[];
    isGuest: boolean;
};

export type ChapterSummary = {
    id: number;
    chapter_number: number;
    title: string;
    status: string;
    published_at: string | null;
};

export type StudioNovelShowProps = {
    novel: {
        id: number;
        title: string;
        slug: string;
        cover_path: string | null;
        synopsis: string | null;
        status: string;
        story_status: string;
        genres: string[];
        submissions: Submission[];
    };
    chapters: ChapterSummary[];
};

export type StudioChapterCreateProps = {
    novel: {
        id: number;
        title: string;
        slug: string;
    };
};

export type StudioChapterEditProps = {
    chapter: {
        id: number;
        title: string;
        content: string | null;
        status: string;
    };
    novel: {
        id: number;
        title: string;
        slug: string;
        status: string;
    };
};
