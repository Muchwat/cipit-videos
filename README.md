# CIPIT Videos

The **CIPIT Videos** plugin is a high-performance WordPress video management tool designed for precision and aesthetics. It features a responsive grid layout with pixel-perfect 16:9 aspect ratio scaling, integrated modal playback, and a pagination system that keeps users anchored to the video section.

---

## 1. Core Features

- **Custom Post Type**: Dedicated "CIPIT Videos" menu in the WordPress dashboard.
- **Golden Ratio Aesthetics**: CSS architecture optimized for visual balance and harmony.
- **Seamless Playback**: High-definition (1080p) YouTube embeds inside a dual-pane modal.
- **Adaptive Styling**: Leverages your theme's CSS variables (e.g., `--primary-color`, `--border-radius`) for native-looking integration.
- **Anchored Pagination**: Switching pages automatically scrolls the user back to the top of the video grid.

---

## 2. Usage & Shortcodes

To display the video grid on any page or post, use the following shortcode:

```php
[cipit_videos]
```

### Shortcode Attributes

You can customize the grid output using these parameters:

| Attribute    | Default              | Description                                      |
|--------------|----------------------|--------------------------------------------------|
| `show`       | `6`                  | Number of videos to display per page.            |
| `order`      | `DESC`               | Sort order (`DESC` for newest first, `ASC` for oldest). |
| `pagination` | `true`               | Toggle pagination visibility (`true`/`false`).   |
| `group`      | `video-grid-section` | Unique ID used for anchored pagination.          |


### Example with Attributes
```php
[cipit_videos show="9" order="ASC" group="home-gallery"]
```

## 3. Managing Videos

### Adding a New Video

1. Navigate to **CIPIT Videos > Add New**.
2. **Title**: Enter the video title.
3. **Editor**: Enter the video description; this appears in the modal sidebar.
4. **Featured Image**: Upload a thumbnail.  
   > 💡 *Note: If no image is uploaded, the plugin automatically pulls the High-Res thumbnail from YouTube.*
5. **Custom Field**: Add a custom field named `_video_youtube_url` and paste the full YouTube link (e.g., `https://www.youtube.com/watch?v=ID`).

---

### Editing an Existing Video

1. Go to **CIPIT Videos > All Videos**.
2. Hover over the video title and click **Edit**.
3. Update any of the fields above as needed.
4. Click **Update** to save changes.

---

### Deleting a Video

1. Navigate to **CIPIT Videos > All Videos**.
2. Check the box next to the video(s) you want to remove.
3. Select **Move to Trash** from the Bulk Actions dropdown.
4. Click **Apply**.

> ⚠️ **Warning**: Deleting a video permanently removes it from your WordPress database. Consider trashing first if you might need to restore it later.

---

### Best Practices

| Practice | Why It Matters |
|----------|---------------|
| ✅ Use descriptive titles | Improves SEO and user navigation |
| ✅ Upload custom thumbnails | Ensures brand consistency across devices |
| ✅ Validate YouTube URLs | Prevents broken embeds and playback errors |
| ✅ Keep descriptions concise | Enhances modal sidebar readability |

---

### Troubleshooting

**Video not displaying?**
- Verify the `_video_youtube_url` custom field contains a valid, public YouTube URL.
- Ensure the post status is set to **Published** (not Draft or Pending).

**Thumbnail not loading?**
- Check that the video ID in the URL is correct.
- Confirm the YouTube video allows embedding (check video settings on YouTube).

**Pagination not scrolling?**
- Ensure the `group` attribute matches the container ID on your page.
- Verify no JavaScript conflicts are preventing the anchor scroll behavior.