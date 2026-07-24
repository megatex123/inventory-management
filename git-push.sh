#!/bin/sh
# Stages, commits, and pushes all changes on the current branch.
# Usage: ./git-push.sh ["commit message"]
set -e

cd "$(dirname "$0")"

BRANCH=$(git rev-parse --abbrev-ref HEAD)
MESSAGE=${1:-"Auto commit: $(date '+%Y-%m-%d %H:%M:%S')"}

# Obsidian's own UI/vault state (window layout, graph settings) churns on
# every open -- not project content, so it's excluded here. .env is already
# gitignored (naming it explicitly here would just trip git's
# already-ignored warning and, with set -e, abort the script).
git add -A -- . ':!docs/QuiviTech/.obsidian' ':!.obsidian'

if git diff --cached --quiet; then
    echo "# Nothing staged to commit #"
    exit 0
fi

echo "# Committing on branch '$BRANCH' #"
git commit -m "$MESSAGE"

echo "# Pushing to origin/$BRANCH #"
git push origin "$BRANCH"
echo "# Done #"
