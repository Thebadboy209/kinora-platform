# KINORA — HTML static edition

A responsive, no-backend video presentation platform. Built with **HTML / CSS / vanilla JavaScript**; compatible with **GitHub Pages**. No PHP, MySQL or server runtime.

## Pages

- `index.html` — video library home
- `explore.html` — search and sort
- `watch.html?id=<id>` — HTML5 player and metadata
- `creators.html` — creators directory
- `creator.html?id=<id>` — creator profile
- `studio.html` — browser-local editor (not a secure admin login)
- `about.html` — about
- `data/site.json` — public video, creator, comment and website settings

## Publish content

The public site reads **`data/site.json` from the GitHub repository**. The `Studio` page is a browser-local preview tool because a static website cannot write to GitHub by itself or authenticate an administrator securely.

1. Open Studio and add/edit creators and videos.
2. Click **Settings & export → Export site.json**.
3. In GitHub, edit `data/site.json` and paste the exported content, then commit to `main`.
4. After GitHub Pages builds the site, public visitors see the changes.

*Do not* enter a GitHub token, password or API key into Studio or any published web page. Browser-local content can be viewed/edited by anyone using that browser; it is not a shared database.

## Video hosting / pone.rs

The Studio provides a native HTML POST form to `https://pone.rs/upload` with field `files[]` in a new tab. The file host may return a JSON URL that is **not** a direct browser-playable video, and upload may be restricted by the host. Paste the playable URL into the Video URL field and verify that it works. Do not rely on this upload method for private media.

## Demo and disclosures

The sample videos and interaction numbers in `data/site.json` are placeholders with **DEMO** labels. The sample video is the MDN example clip (CC0 video example) on the watch page; other demo cards deliberately have no playable URL. Comments are explicitly labeled demo and not presented as real viewer engagement. All statistics are supplied manually and must not be treated as measured platform activity.

## GitHub Pages

Repository → **Settings → Pages → Build and deployment → Source: Deploy from a branch → Branch: main / (root) → Save**.

Published URL (after enabling Pages): `https://thebadboy209.github.io/kinora-platform/`

## Deployment source

This repo may still contain PHP files from an older version, which are not used by the static HTML edition. The HTML pages are the active GitHub Pages entry points.
