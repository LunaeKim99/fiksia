export type Genre = {
    id: number;
    name: string;
    slug: string;
};

export type NovelAuthor = {
    id?: number;
    name: string;
    username: string;
};

export type NovelCard = {
    id: number;
    title: string;
    slug: string;
    coverAvailable: boolean;
    coverUrl: string | null;
    story_status: 'ongoing' | 'completed' | 'hiatus';
    story_status_label: string;
    author: NovelAuthor;
    synopsis: string;
    chapterCount: number;
    genres: Genre[];
};

export type ChapterSummary = {
    id: number;
    chapter_number: number;
    title: string;
    is_free: boolean;
    published_at: string | null;
    status: string;
};

export type NovelDetail = {
    id: number;
    title: string;
    slug: string;
    coverAvailable: boolean;
    coverUrl: string | null;
    synopsis: string | null;
    story_status: 'ongoing' | 'completed' | 'hiatus';
    story_status_label: string;
    author: Required<Pick<NovelAuthor, 'id' | 'name' | 'username'>>;
    genres: Genre[];
    published_at: string | null;
    chapterCount: number;
    chapters: ChapterSummary[];
};

export type ChapterListItem = {
    id: number;
    chapter_number: number;
    title: string;
};

export type ChapterReader = {
    id: number;
    chapter_number: number;
    title: string;
    content: string;
    published_at: string | null;
};

export type PageLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type ContinueReadingNovel = NovelCard & {
    chapterNumber: number;
    lastReadAt: string | null;
};

export type HomeProps = {
    latestNovels: NovelCard[];
    continueReading: ContinueReadingNovel[];
};

export type ExploreProps = {
    novels: {
        data: NovelCard[];
        links: PageLink[];
    };
    genres: Genre[];
    activeFilters: {
        q: string;
        genre: string;
        status: string;
        sort: 'latest' | 'popular';
    };
    totalResults: number;
};

export type NovelShowProps = {
    novel: NovelDetail;
    isOwner: boolean;
    isFollowing: boolean;
    isSaved: boolean;
    readingProgress: {
        chapterNumber: number;
        lastReadAt: string | null;
    } | null;
    latestChapterId: number | null;
};

import type { AppearanceSettings, ReadingSettings } from './settings';

export type ReaderShowProps = {
    novel: {
        id: number;
        title: string;
        slug: string;
    };
    chapter: ChapterReader;
    prevChapter: number | null;
    nextChapter: number | null;
    chapterList: ChapterListItem[];
    isOwner: boolean;
    isGuest: boolean;
    isBookmarked: boolean;
    user: {
        id: number;
        name: string;
    } | null;
    appearanceSettings: AppearanceSettings | null;
    readingSettings: ReadingSettings | null;
};
