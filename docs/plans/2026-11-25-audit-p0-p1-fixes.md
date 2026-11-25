# Fiksia Audit Fixes (P0 + P1) Implementation Plan

> **For the executing agent:** REQUIRED SUB-SKILL: use `subagent-driven-development` in this session. One implementer subagent per task, then a spec-compliance reviewer, then a code-quality reviewer.

**Goal:** Menutup blocker dan bug prioritas tinggi hasil audit 2026-11-25 tanpa mengubah perilaku produk lain.

**Architecture:** Perbaikan menyentuh application layer (snapshot pengajuan, validasi konten), domain enum (transisi `rejected`), policy (kunci edit saat review), controller (query string aman, props review), dan halaman React (review workspace, reader, editor).

**Tech Stack:** Laravel 13, PHP 8.5, Pest, PHPStan level 7, React 19 + Inertia 3 + TypeScript, Tailwind 4, Vite 8.

**Worktree:** `C:\Users\KimY\.local\share\opencode\worktree\cb0a14\fix-audit-p0-p1` (branch `fix/audit-p0-p1`, based on `main` `4edf299`). Jangan menyentuh `C:\laragon\www\Fiksia`.

**Baseline:** `php artisan test` 134 pass / 728 assertions; PHPStan 0 error; `tsc` 0 error; `npm run build` sukses. `npm run check` gagal hanya karena formatting `README.md` (pre-existing, diperbaiki sebagai prep).

---

### Task 0 (controller prep): Normalize formatting so the gate is green

**Files:**

- Modify: `README.md`

**Steps:**

1. Run `npm run check:fix`.
2. Verify `git status --short` only reports `README.md` (and this plan file). Revert any unrelated auto-format if present.
3. Run `npm run check`; expect success.
4. Commit only the docs/format changes:
   `git add README.md docs/plans/2026-11-25-audit-p0-p1-fixes.md`
   `git commit -m "docs: add audit fix plan and normalize README formatting"`

---

### Task 1: P0-1 — Snapshot chapter content and lock editing during initial review

**Problem:** Moderator cannot read chapter content; the author can still edit a draft chapter after submitting for review, so the approved content may differ from what was reviewed. Verified with HTTP tests.

**Files:**

- Modify: `app/Application/Moderation/Commands/SubmitInitialReview/SubmitInitialReviewHandler.php`
- Modify: `app/Http/Controllers/Moderation/ModerationQueueController.php`
- Modify: `app/Policies/ChapterPolicy.php`
- Modify: `resources/js/types/library.ts`
- Modify: `resources/js/pages/Moderation/Submission.tsx`
- Modify: `resources/js/pages/Studio/Novels/Show.tsx`
- Test: `tests/Feature/InitialReviewFlowTest.php`
- Test: `tests/Feature/Moderation/ModerationQueueTest.php`
- Test: `tests/Feature/Studio/StudioTest.php`

**Step 1: Write failing tests**

In `tests/Feature/InitialReviewFlowTest.php`, after the successful submission assertion, assert the chapter snapshot stores content:

```php
$chapterItems = SubmissionItem::query()
    ->where('submission_id', $submission->id)
    ->where('item_type', 'chapter')
    ->orderBy('sort_order')
    ->get();

expect($chapterItems)->toHaveCount(2)
    ->and($chapterItems[0]->snapshot['content'])->toBe('Isi bab satu.');
```

Add `use App\Models\SubmissionItem;`.

In `tests/Feature/Moderation/ModerationQueueTest.php`:

- Give the fixture chapter explicit content: `'content' => 'Isi bab yang direview.'`.
- Add `'content' => $chapter->content` to the submission item snapshot in `moderationFixture()`.
- Assert the review workspace exposes the content:

```php
->where('submission.items.0.content', 'Isi bab yang direview.')
```

- Add a test proving the reviewed payload is the snapshot, not live content:

```php
it('shows the submitted snapshot even if the chapter changes later', function () {
    $fixture = moderationFixture();
    $moderator = moderationUser(UserRole::Moderator);

    $fixture['chapter']->update(['content' => 'Konten yang ditukar setelah submit.']);

    $this->actingAs($moderator)
        ->get(route('moderation.submissions.show', ['submission' => $fixture['submission']->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('submission.items.0.content', 'Isi bab yang direview.')
        );
});
```

In `tests/Feature/Studio/StudioTest.php` add:

```php
it('blocks chapter edits while the novel is in initial review', function () {
    $owner = User::factory()->create();
    $novel = Novel::factory()->create([
        'user_id' => $owner->id,
        'status' => 'initial_review',
    ]);
    $chapter = Chapter::factory()->create([
        'novel_id' => $novel->id,
        'status' => 'draft',
        'content' => 'Konten yang dikirim.',
    ]);

    $this->actingAs($owner)->post("/studio/chapters/{$chapter->id}", [
        'title' => 'Judul Baru',
        'content' => 'Konten pengganti.',
    ])->assertForbidden();

    expect($chapter->fresh()->content)->toBe('Konten yang dikirim.');

    $novel->update(['status' => 'revision_required']);

    $this->actingAs($owner)->post("/studio/chapters/{$chapter->id}", [
        'title' => 'Judul Baru',
        'content' => 'Konten revisi.',
    ])->assertRedirect();

    expect($chapter->fresh()->content)->toBe('Konten revisi.');
});
```

**Step 2: Run tests and confirm they fail**

Run: `php artisan test --compact --filter="snapshot|initial review|snapshot even if"`
Expected: FAIL (snapshot missing content, review prop missing, edit still allowed).

**Step 3: Implement**

1. `SubmitInitialReviewHandler.php` — add content to the chapter item snapshot:

```php
$items[] = [
    'item_type' => SubmissionItemType::Chapter,
    'chapter_id' => $chapterId,
    'snapshot' => [
        'chapter_number' => $chapter->chapterNumber,
        'title' => $chapter->title,
        'content' => $chapter->content,
    ],
    'sort_order' => $sortOrder++,
];
```

2. `ChapterPolicy.php` — block edits while the novel is being reviewed:

```php
public function update(User $user, Chapter $chapter): bool
{
    $novel = $chapter->novel;

    return $novel->user_id === $user->id
        && $chapter->status === ChapterStatus::Draft
        && $novel->status !== NovelStatus::InitialReview;
}
```

3. `ModerationQueueController.php` — expose snapshot content:

```php
'content' => $this->stringValue($snapshot['content'] ?? null),
```

4. `resources/js/types/library.ts` — add `content: string | null;` to `ModerationSubmissionItem`.

5. `resources/js/pages/Moderation/Submission.tsx` — render the snapshot body for chapter items inside the item card:

```tsx
{
    item.itemType === 'chapter' ? (
        item.content ? (
            <div className="mt-3 max-h-96 overflow-auto rounded-xl border border-outline-variant bg-surface p-3">
                <p className="text-xs font-medium text-on-surface-variant">
                    Isi bab (snapshot saat diajukan)
                </p>
                <p className="mt-2 whitespace-pre-wrap text-sm leading-6 text-on-surface">
                    {item.content}
                </p>
            </div>
        ) : (
            <p className="mt-2 text-xs text-warning">
                Isi bab tidak tersedia pada pengajuan lama.
            </p>
        )
    ) : null;
}
```

6. `resources/js/pages/Studio/Novels/Show.tsx` — hide Edit while the novel is in review:

```tsx
{chapter.status === 'draft' && novel.status !== 'initial_review' && (
    <Link ...>Edit</Link>
)}
```

**Step 4: Run tests and full gates**

```
php artisan test --compact
vendor/bin/phpstan analyse --no-progress --memory-limit=1G
npm run types:check
npm run check
npm run build
```

Expected: all pass.

**Step 5: Commit**

```
git add app/Application/Moderation/Commands/SubmitInitialReview/SubmitInitialReviewHandler.php \
        app/Http/Controllers/Moderation/ModerationQueueController.php \
        app/Policies/ChapterPolicy.php \
        resources/js/types/library.ts \
        resources/js/pages/Moderation/Submission.tsx \
        resources/js/pages/Studio/Novels/Show.tsx \
        tests/Feature/InitialReviewFlowTest.php \
        tests/Feature/Moderation/ModerationQueueTest.php \
        tests/Feature/Studio/StudioTest.php
git commit -m "fix(moderation): snapshot chapter content and lock edits during review"
```

---

### Task 2: P0-2 — Durable autosave in the chapter editor

**Problem:** A pending debounce is cancelled on SPA navigation and the save indicator can stay stuck after HTTP/network errors.

**Files:**

- Modify: `resources/js/pages/Studio/Chapters/Edit.tsx`

**Step 1: Refactor saving into one promise-based function**

- Keep `latestRef` updated on every render.
- Add `dirtyRef` mirroring `isDirty`.
- Add `persist(values: { title: string; content: string }): Promise<boolean>` using `router.post` with:
    - `preserveScroll: true`, `preserveState: true`
    - `onSuccess`: update `initialRef`, `setIsDirty(false)`, `setLastSavedAt`, `resolve(true)`
    - `onError`: `setSaveError('Gagal menyimpan')`, `resolve(false)`
    - `onHttpException`: `setSaveError('Gagal menyimpan (server)')`, `resolve(false)`
    - `onNetworkError`: `setSaveError('Tidak dapat terhubung')`, `resolve(false)`
    - `onCancel`: `resolve(false)`
    - `onFinish`: always `setSaving(false)`
- `autosave()` and `saveDraft()` both call `persist`.

**Step 2: Flush before Inertia navigation**

Register a `router.on('before', handler)` listener in a `useEffect`:

- If `dirtyRef.current === false`, do nothing.
- Otherwise `event.preventDefault()`, call `persist(latestRef.current)`.
- On success set `dirtyRef.current = false`, `setIsDirty(false)`, then replay the navigation with `router.visit(event.detail.visit.url, { method: event.detail.visit.method, data: event.detail.visit.data, preserveScroll: true })`.
- Unregister with `router.off('before', handler)` in cleanup.
- If the Inertia v3 typings differ, inspect `node_modules/@inertiajs/core/types` and adapt. Do not use `any`; if the event shape is not typed, use the exported event type.

**Step 3: Verify**

```
npm run types:check
npm run check
npm run build
```

Manual check (report it, do not skip): type in the editor, click a link within 2 seconds; navigation must wait for the save and the text must persist after returning. Simulate an HTTP error by blocking the request; the save button must become usable again after the failure.

**Step 4: Commit**

```
git add resources/js/pages/Studio/Chapters/Edit.tsx
git commit -m "fix(studio): make autosave durable and recoverable"
```

---

**Known limitations / follow-ups (recorded after review):**

- Browser back/forward (popstate) bypasses `router.on('before')`; unsaved edits can still be lost that way and Inertia's popstate handler cancels in-flight requests. Follow-up (P2): capture-phase `popstate` guard that restores the URL, flushes, then replays history navigation; needs its own tests against Inertia's history stack.
- The 419 inline copy is status-branch verified but was not manually reproduced; include it in the PR checklist.
- Publish-while-dirty is intercepted and replayed as a state-changing POST but was not browser-tested; verify the chapter is published **and** the final keystrokes are included.
- Invariant: `initialRef` must be assigned before `settle()` resolves in `onSuccess`, because the replay guard reads it. Add a code comment if this file is refactored.
- Review minors deferred: the `autosave` catch does not reset `saving` if a rejection ever occurs (unreachable today); exhausted flush attempts return `false` silently (rare, safe).

**Manual verification checklist (re-run for any change to this file):**

1. Type, click a navigation link within 2s → save POST completes before the next GET; text persists.
2. Abort the save POST, edit, click a link → stay on page, inline error, Save button usable.
3. Wait 3s → normal autosave, no console errors.
4. Delay the save, type newer content, click a link → serialized saves; the latest content persists.
5. Force 403 (novel `initial_review`) → inline "Bab terkunci selama pengajuan direview.", no modal, usable button.
6. Hard reload while dirty → browser warning.
7. Type then revert to the saved text → no pointless save.

### Task 3: P1-1 — Reject non-string Explore query params instead of 500

**Files:**

- Modify: `app/Http/Controllers/ExploreController.php`
- Test: `tests/Feature/Catalog/CatalogTest.php`

**Step 1: Failing test**

```php
it('ignores array query params on explore instead of crashing', function () {
    foreach (['q[]=x', 'genre[]=x', 'status[]=x'] as $query) {
        $this->get('/explore?'.$query)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('activeFilters.q', '')
                ->where('activeFilters.genre', '')
                ->where('activeFilters.status', '')
            );
    }
});
```

Run: `php artisan test --compact --filter="array query params"` → FAIL (500).

**Step 2: Implement**

Add a private helper and use it for `q`, `genre`, `status`:

```php
private function queryString(Request $request, string $key, int $maxLength): string
{
    $value = $request->query($key);

    return is_string($value) ? mb_substr(trim($value), 0, $maxLength) : '';
}
```

```php
$q = $this->queryString($request, 'q', 120);
$genre = $this->queryString($request, 'genre', 80);
$status = $this->queryString($request, 'status', 20);
```

`sort` handling stays as-is (`in_array` is already safe).

**Step 3: Verify and commit**

```
php artisan test --compact
vendor/bin/phpstan analyse --no-progress --memory-limit=1G
npm run check
git add app/Http/Controllers/ExploreController.php tests/Feature/Catalog/CatalogTest.php
git commit -m "fix(explore): ignore non-string query parameters"
```

---

### Task 4: P1-3 — Require chapter content before review and publish

**Files:**

- Modify: `app/Application/Moderation/Commands/SubmitInitialReview/SubmitInitialReviewHandler.php`
- Modify: `app/Application/Novel/Commands/PublishChapter/PublishChapterHandler.php`
- Test: `tests/Feature/Studio/StudioTest.php`
- Test: `tests/Feature/InitialReviewFlowTest.php`

**Step 1: Failing tests**

Add to `StudioTest.php`:

```php
it('blocks publishing a chapter without content', function () {
    $owner = User::factory()->create();
    $novel = Novel::factory()->published()->create(['user_id' => $owner->id]);
    $chapter = Chapter::factory()->create([
        'novel_id' => $novel->id,
        'status' => 'draft',
        'content' => "  \n ",
    ]);

    $this->actingAs($owner)
        ->post(route('studio.chapters.publish', ['chapter' => $chapter->id]))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($chapter->fresh()->status->value)->toBe('draft');
});
```

Add to `InitialReviewFlowTest.php` (or `StudioTest.php`):

```php
it('blocks submitting a review when a chapter has no content', function () {
    $author = User::factory()->create();
    $novel = Novel::factory()->create(['user_id' => $author->id, 'status' => 'draft']);
    $chapter = Chapter::factory()->create([
        'novel_id' => $novel->id,
        'status' => 'draft',
        'content' => null,
    ]);

    $this->actingAs($author)
        ->post("/studio/novels/{$novel->id}/submit-review", ['chapter_ids' => [$chapter->id]])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(InitialSubmission::query()->where('novel_id', $novel->id)->exists())->toBeFalse();
});
```

Run and confirm both fail.

**Step 2: Implement**

`PublishChapterHandler` after the transition check:

```php
if (trim((string) $chapter->content) === '') {
    throw new InvalidStatusTransitionException('Bab harus memiliki isi sebelum diterbitkan.');
}
```

`SubmitInitialReviewHandler` inside the chapter loop (before appending the item):

```php
if ($chapter->status !== ChapterStatus::Draft) {
    throw new InvalidStatusTransitionException('Hanya draft bab yang dapat diajukan.');
}

if (trim((string) $chapter->content) === '') {
    throw new DomainException('Semua bab yang diajukan harus memiliki isi.');
}
```

Add imports: `App\Domain\Chapter\Enums\ChapterStatus`, `App\Domain\Shared\Exceptions\InvalidStatusTransitionException`.

**Step 3: Verify and commit**

```
php artisan test --compact
vendor/bin/phpstan analyse --no-progress --memory-limit=1G
git add app/Application/Novel/Commands/PublishChapter/PublishChapterHandler.php \
        app/Application/Moderation/Commands/SubmitInitialReview/SubmitInitialReviewHandler.php \
        tests/Feature/Studio/StudioTest.php \
        tests/Feature/InitialReviewFlowTest.php
git commit -m "fix(content): require chapter content before review and publish"
```

---

### Task 5: P1-2 — Allow resubmission after rejection

**Files:**

- Modify: `app/Domain/Novel/Enums/NovelStatus.php`
- Modify: `app/Policies/NovelPolicy.php`
- Modify: `resources/js/pages/Studio/Novels/Show.tsx`
- Test: `tests/Feature/InitialReviewFlowTest.php`
- Test: `tests/Feature/Studio/StudioTest.php`

**Step 1: Failing tests**

Add to `InitialReviewFlowTest.php`:

```php
it('allows resubmission after a rejection with notes', function () {
    $author = User::factory()->create();
    $moderator = User::factory()->create();

    $novel = app(CreateNovelHandler::class)->handle(new CreateNovelCommand(
        userId: $author->id,
        title: 'Novel Ditolak Lalu Diperbaiki',
    ));

    $chapter = app(CreateDraftChapterHandler::class)->handle(new CreateDraftChapterCommand(
        novelId: $novel->id,
        authorId: $author->id,
        title: 'Bab Satu',
        content: 'Isi yang diperbaiki.',
    ));

    $submission = app(SubmitInitialReviewHandler::class)->handle(new SubmitInitialReviewCommand(
        novelId: $novel->id,
        authorId: $author->id,
        chapterIds: [$chapter->id],
    ));

    app(ModerateInitialSubmissionHandler::class)->handle(new ModerateInitialSubmissionCommand(
        submissionId: $submission->id,
        moderatorId: $moderator->id,
        decision: SubmissionStatus::Rejected,
        notes: 'Perbaiki dulu.',
    ));

    $resubmission = app(SubmitInitialReviewHandler::class)->handle(new SubmitInitialReviewCommand(
        novelId: $novel->id,
        authorId: $author->id,
        chapterIds: [$chapter->id],
    ));

    expect($resubmission->status)->toBe(SubmissionStatus::Submitted)
        ->and(Novel::query()->findOrFail($novel->id)->status)->toBe(NovelStatus::InitialReview);
});
```

Add an HTTP-level test in `StudioTest.php`:

```php
it('allows a rejected novel to be resubmitted through the studio', function () {
    $author = User::factory()->create();
    $novel = Novel::factory()->create(['user_id' => $author->id, 'status' => 'rejected']);
    $chapter = Chapter::factory()->create([
        'novel_id' => $novel->id,
        'status' => 'draft',
        'content' => 'Isi revisi.',
    ]);

    $this->actingAs($author)
        ->post("/studio/novels/{$novel->id}/submit-review", ['chapter_ids' => [$chapter->id]])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($novel->fresh()->status->value)->toBe('initial_review');
});
```

Run and confirm 403/no transition failures.

**Step 2: Implement**

- `NovelStatus.php`: `'rejected' => [self::InitialReview],`
- `NovelPolicy::submitForReview()`: include `NovelStatus::Rejected` in the allowed array.
- `Studio/Novels/Show.tsx` `canSubmit`: include `novel.status === 'rejected'`.

**Step 3: Verify and commit**

```
php artisan test --compact
vendor/bin/phpstan analyse --no-progress --memory-limit=1G
npm run types:check
npm run check
git add app/Domain/Novel/Enums/NovelStatus.php \
        app/Policies/NovelPolicy.php \
        resources/js/pages/Studio/Novels/Show.tsx \
        tests/Feature/InitialReviewFlowTest.php \
        tests/Feature/Studio/StudioTest.php
git commit -m "fix(moderation): allow rejected novels to be resubmitted"
```

---

### Task 6: P1-4 — Hide the Reader edit link unless the chapter is editable

**Files:**

- Modify: `app/Http/Controllers/ReaderController.php`
- Modify: `resources/js/types/catalog.ts`
- Modify: `resources/js/pages/Reader/Show.tsx`
- Test: `tests/Feature/Catalog/CatalogTest.php`

**Step 1: Failing test**

In the reader feature test in `CatalogTest.php`, assert the chapter status is exposed:

```php
->where('chapter.status', 'published')
```

Run: `php artisan test --compact --filter="reads published chapter"` → FAIL.

**Step 2: Implement**

- `ReaderController` chapter payload: `'status' => $chapterModel->status->value,`
- `ChapterReader` type: `status: string;`
- `Reader/Show.tsx`: change the owner edit block to `{isOwner && chapter.status === 'draft' ? (...) : null}`.

**Step 3: Verify and commit**

```
php artisan test --compact
vendor/bin/phpstan analyse --no-progress --memory-limit=1G
npm run types:check
npm run check
git add app/Http/Controllers/ReaderController.php \
        resources/js/types/catalog.ts \
        resources/js/pages/Reader/Show.tsx \
        tests/Feature/Catalog/CatalogTest.php
git commit -m "fix(reader): only show edit action for editable chapters"
```

---

### Task 7: Final verification

1. `php artisan test --compact` — all pass.
2. `vendor/bin/phpstan analyse --no-progress --memory-limit=1G` — 0 errors.
3. `npm run types:check` — clean.
4. `npm run check` — clean.
5. `npm run build` — success.
6. `composer ci:check` — allow it to run; report the result.
7. Review `git log --oneline main..HEAD` and the final diff.
8. Use `superpowers:finishing-a-development-branch` to present merge options.

---

## Constraints

- Never modify `C:\laragon\www\Fiksia`; work only inside the worktree above.
- Do not add dependencies.
- Do not weaken or delete existing tests.
- Keep UI copy in Indonesian and code identifiers in English, matching the codebase.
- Commit per task; do not bundle unrelated changes.
- If a task cannot be completed safely, stop and report instead of guessing.
