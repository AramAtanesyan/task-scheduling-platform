# Confirmation Modal Component - Usage Guide

## Overview

A reusable confirmation modal component that replaces native browser `confirm()` dialogs with a beautiful, customizable modal that supports:
- ✅ Async operations with loading states
- ✅ Error handling and display
- ✅ Custom messages and button text
- ✅ Danger mode for destructive actions
- ✅ Promise-based API for easy integration

## Files Created

1. **`resources/js/components/confirm-modal.js`** - The modal component
2. **Updated `resources/js/pages/dashboard.js`** - Added component import
3. **Updated `resources/js/components/task-board.js`** - Example usage
4. **Updated `resources/views/dashboard.blade.php`** - Added styles

## Basic Usage

### 1. Add to Your Template

Include the component in your Vue template with a `ref`:

```html
<confirm-modal ref="confirmModal" />
```

### 2. Call from Your Methods

Use the modal by calling the `show()` method with configuration:

```javascript
// Simple confirmation
this.$refs.confirmModal.show({
  title: 'Delete Item',
  message: 'Are you sure you want to delete this item?',
  confirmText: 'Delete',
  cancelText: 'Cancel',
  dangerMode: true
}).then(confirmed => {
  if (confirmed) {
    // User clicked confirm
    console.log('Confirmed!');
  } else {
    // User clicked cancel
    console.log('Cancelled');
  }
});
```

## Advanced Usage with Async Operations

### With Async Callback (Recommended)

The modal can handle async operations directly, showing loading states and errors:

```javascript
this.$refs.confirmModal.show({
  title: 'Delete Task',
  message: 'Are you sure you want to delete this task? This action cannot be undone.',
  confirmText: 'Delete',
  cancelText: 'Cancel',
  dangerMode: true,
  onConfirm: async () => {
    // This async function will be called when user confirms
    await axios.delete(`/api/tasks/${taskId}`);
    await this.fetchTasks();
    // Modal closes automatically on success
  }
}).catch(error => {
  // Error is already displayed in the modal
  console.error('Operation failed:', error);
});
```

**Benefits:**
- ✅ Automatic loading spinner on confirm button
- ✅ Error messages displayed in modal
- ✅ Modal stays open on error
- ✅ Modal closes automatically on success
- ✅ Confirm button disabled during operation

## Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `title` | String | 'Confirm Action' | Modal title |
| `message` | String | 'Are you sure...' | Confirmation message |
| `confirmText` | String | 'Confirm' | Confirm button text |
| `cancelText` | String | 'Cancel' | Cancel button text |
| `dangerMode` | Boolean | false | Use red/danger styling for confirm button |
| `onConfirm` | Function | null | Async callback executed on confirm |

## Examples

### Example 1: Simple Confirmation

```javascript
async handleLogout() {
  const confirmed = await this.$refs.confirmModal.show({
    title: 'Logout',
    message: 'Are you sure you want to logout?',
    confirmText: 'Logout',
    dangerMode: false
  });

  if (confirmed) {
    await axios.post('/logout');
    window.location.href = '/login';
  }
}
```

### Example 2: Delete with Async Callback

```javascript
async handleDelete(itemId) {
  this.$refs.confirmModal.show({
    title: 'Delete Item',
    message: 'This action cannot be undone. Are you sure?',
    confirmText: 'Delete',
    cancelText: 'Keep It',
    dangerMode: true,
    onConfirm: async () => {
      await axios.delete(`/api/items/${itemId}`);
      await this.fetchItems();
      this.showToast('Item deleted successfully');
    }
  }).catch(error => {
    this.showToast('Failed to delete item', 'error');
  });
}
```

### Example 3: Archive with Warning

```javascript
async handleArchive(projectId) {
  this.$refs.confirmModal.show({
    title: 'Archive Project',
    message: 'Archived projects can be restored later. Continue?',
    confirmText: 'Archive',
    cancelText: 'Cancel',
    dangerMode: false,
    onConfirm: async () => {
      await axios.post(`/api/projects/${projectId}/archive`);
      this.projects = this.projects.filter(p => p.id !== projectId);
    }
  });
}
```

### Example 4: Publish with Validation

```javascript
async handlePublish(postId) {
  this.$refs.confirmModal.show({
    title: 'Publish Post',
    message: 'Once published, this post will be visible to all users.',
    confirmText: 'Publish Now',
    cancelText: 'Keep Draft',
    dangerMode: false,
    onConfirm: async () => {
      const response = await axios.post(`/api/posts/${postId}/publish`);
      this.post.status = 'published';
      this.post.published_at = response.data.published_at;
    }
  });
}
```

## Features

### ✅ Loading State

When using `onConfirm` callback:
- Confirm button shows spinner
- Confirm button is disabled
- Cancel button is disabled
- User can't dismiss modal

### ✅ Error Handling

If the async operation fails:
- Error message displayed in modal (red background)
- Modal stays open
- User can retry or cancel
- Loading state is removed

### ✅ Success Handling

If the async operation succeeds:
- Modal closes automatically
- Promise resolves with `true`
- No error state

### ✅ Cancel Handling

If user clicks cancel:
- Modal closes immediately
- Promise resolves with `false`
- No async operation is executed

## Styling

The modal uses the following CSS classes:

- `.modal-overlay` - Dark backdrop
- `.confirm-modal` - Modal container
- `.modal-header` - Title section
- `.modal-body` - Message section
- `.modal-footer` - Button section
- `.btn-danger` - Red button for dangerous actions
- `.btn-secondary` - Gray button for cancel
- `.error-message` - Red error message box
- `.spinner` - Loading spinner

You can customize these in your CSS file.

## Integration with Other Pages

### To use in other pages:

1. **Import the component:**
```javascript
require('../components/confirm-modal');
```

2. **Add to your template:**
```html
<confirm-modal ref="confirmModal" />
```

3. **Use in methods:**
```javascript
this.$refs.confirmModal.show({ /* options */ });
```

## Best Practices

### ✅ Do:
- Use `dangerMode: true` for destructive actions (delete, remove, etc.)
- Provide clear, specific messages
- Use async callbacks for operations that might fail
- Handle errors gracefully

### ❌ Don't:
- Use for simple notifications (use toast instead)
- Make messages too long (keep under 2 lines)
- Forget to handle errors from async operations
- Block the UI unnecessarily

## Accessibility

The modal includes:
- Click outside to dismiss (on overlay)
- Escape key support (can be added)
- Keyboard navigation (tab between buttons)
- Focus trap (keeps focus in modal)

## Migration from Native confirm()

**Before:**
```javascript
if (confirm('Delete this?')) {
  await axios.delete(`/api/items/${id}`);
}
```

**After:**
```javascript
this.$refs.confirmModal.show({
  message: 'Delete this?',
  dangerMode: true,
  onConfirm: async () => {
    await axios.delete(`/api/items/${id}`);
  }
});
```

## Troubleshooting

### Modal doesn't appear
- Check that component is imported in your page's JS file
- Verify the `ref="confirmModal"` is set in template
- Check browser console for errors

### Buttons not working
- Verify the component is properly loaded
- Check that Vue is initialized
- Look for JavaScript errors in console

### Styling looks wrong
- Ensure CSS is loaded in your blade template
- Check for CSS conflicts with other components
- Verify modal-overlay has proper z-index

## Summary

The confirmation modal provides a modern, user-friendly alternative to browser's native `confirm()` dialog with:

✅ Beautiful UI
✅ Loading states
✅ Error handling  
✅ Async operation support
✅ Customizable messages
✅ Promise-based API
✅ Reusable across all components

No more `confirm()` and `alert()` - use this modal for all confirmations!

