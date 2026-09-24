import type { NovelCard, PageLink } from './catalog';

export type LibraryTab = 'following' | 'saved' | 'reading' | 'finished';

export type LibraryRow = NovelCard & {
    lastReadAt: string | null;
    currentChapterNumber: number | null;
};

export type LibraryCounts = Record<LibraryTab, number>;

export type LibraryProps = {
    activeTab: LibraryTab;
    counts: LibraryCounts;
    items: LibraryRow[];
    links: PageLink[];
    currentPage: number;
    lastPage: number;
};

export type ModerationStatus =
    | 'submitted'
    | 'reviewing'
    | 'approved'
    | 'revision_required'
    | 'rejected';

export type ModerationItemType = 'cover' | 'synopsis' | 'metadata' | 'chapter';

export type ModerationQueueItem = {
    id: number;
    novel: {
        title: string;
        slug: string;
    };
    submittedBy: {
        id: number;
        name: string;
    };
    submittedAt: string | null;
    status: ModerationStatus;
    statusLabel: string;
    reviewNotes: string | null;
};

export type ModerationQueueProps = {
    submissions: {
        data: ModerationQueueItem[];
        links: PageLink[];
    };
};

export type ModerationSubmissionItem = {
    id: number;
    itemType: ModerationItemType;
    label: string;
    chapterId: number | null;
    chapterNumber: number | null;
    title: string | null;
    snapshot: Record<string, unknown> | null;
};

export type ModerationSubmissionProps = {
    submission: {
        id: number;
        status: ModerationStatus;
        statusLabel: string;
        isDecided: boolean;
        reviewNotes: string | null;
        submittedAt: string | null;
        decidedAt: string | null;
        author: {
            id: number;
            name: string;
        };
        novel: {
            id: number;
            title: string;
            slug: string;
            synopsis: string | null;
            coverAvailable: boolean;
            coverUrl: string | null;
            genres: Array<{
                id: number;
                name: string;
            }>;
        };
        items: ModerationSubmissionItem[];
    };
};

export type ModerationDecision = 'approved' | 'revision_required' | 'rejected';
