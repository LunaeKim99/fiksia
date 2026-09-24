---
paths:
    - 'docs/evaluasi/**'
---

# Evaluasi

## Write per-task evaluation file after each completed task

After finishing any task, create/update `docs/evaluasi/evaluasi-task-N.md` containing: ringkasan, status akhir (command results), file dibuat/dihapus, keputusan penting, kualitas, risiko sisa, lanjut ke task berikutnya. Use subagent-driven-development workflow with spec review + code quality review before marking done. Update `docs/evaluasi/README.md` as index.

## Write per-task evaluation file after each completed task

Setelah tiap task selesai (di workflow subagent-driven-development), tulis `docs/evaluasi/evaluasi-task-N.md`: ringkasan, status akhir+command hasil, file dibuat/diubah/dihapus, keputusan+asumsi, kualitas+review, risiko sisa, lanjut ke task berikutnya. Bahasa Indonesia, markdown. Format mengikuti evaluasi-task-1..4.md yang sudah ada.
