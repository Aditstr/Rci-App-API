export function quotaLabel(usage) {
    if (!usage || usage.limit === null || usage.limit === undefined) return null;

    const limit = Number(usage.limit);

    if (!Number.isFinite(limit)) return null;

    const remaining = Math.max(0, Number(usage.remaining) || 0);

    return `Sisa ${remaining} dari ${limit} pertanyaan AI hari ini`;
}
