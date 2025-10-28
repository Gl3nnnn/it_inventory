<!-- Notification Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="notificationModalLabel"><i class="bi bi-bell-fill"></i> Notifications</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="notificationModalBody">
        <!-- Notifications will be injected here by JavaScript -->
      </div>
      <div class="modal-footer justify-content-between">
        <div>
            <button type="button" class="btn btn-success" id="markAllReadBtn">Mark all as read</button>
            <button type="button" class="btn btn-danger" id="clearAllBtn">Clear all</button>
        </div>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // All the notification javascript from dashboard.php
    async function fetchNotifications() {
      try {
        const response = await fetch('fetch_notifications.php');
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        const data = await response.json();
        return data;
      } catch (error) {
        console.error('Error fetching notifications:', error);
        return { notifications: [], unread_count: 0 };
      }
    }

    function updateNotificationBadge(count) {
      const badge = document.getElementById('notificationBadge');
      if (count > 0) {
        badge.textContent = count;
        badge.style.display = 'flex';
      } else {
        badge.style.display = 'none';
      }
    }

    function getNotificationIcon(type) {
      const icons = {
        'info': 'bi-info-circle-fill text-info',
        'warning': 'bi-exclamation-triangle-fill text-warning',
        'danger': 'bi-x-circle-fill text-danger',
        'success': 'bi-check-circle-fill text-success'
      };
      return icons[type] || 'bi-bell-fill';
    }

    function formatTime(timestamp) {
      const date = new Date(timestamp);
      const now = new Date();
      const diff = now - date;
      const minutes = Math.floor(diff / 60000);
      const hours = Math.floor(diff / 3600000);
      const days = Math.floor(diff / 86400000);

      if (minutes < 1) return 'Just now';
      if (minutes < 60) return `${minutes}m ago`;
      if (hours < 24) return `${hours}h ago`;
      if (days < 7) return `${days}d ago`;
      return date.toLocaleDateString();
    }

    function showNotificationsInModal(notifications) {
        const modalBody = document.getElementById('notificationModalBody');
        if (notifications.length === 0) {
            modalBody.innerHTML = '<div class="text-center py-4"><i class="bi bi-bell-slash-fill text-muted" style="font-size: 2rem;"></i><p class="mt-2 text-muted">No notifications found.</p></div>';
        } else {
            let notificationsHTML = '<div class="list-group list-group-flush">';
            notifications.forEach(notif => {
                const isClickable = notif.link ? '' : 'disabled';
                const href = notif.link || '#';
                const readClass = notif.is_read ? 'notification-read' : '';

                notificationsHTML += `
                    <a href="${href}" class="list-group-item list-group-item-action notification-item ${isClickable} ${readClass}" data-id="${notif.id}" data-read="${notif.is_read}">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><i class="bi ${getNotificationIcon(notif.type)} me-2"></i>${notif.title}</h6>
                            <small class="text-muted">${formatTime(notif.created_at)}</small>
                        </div>
                        <p class="mb-1 small text-muted">${notif.message}</p>
                    </a>
                `;
            });
            notificationsHTML += '</div>';
            modalBody.innerHTML = notificationsHTML;
        }
    }

    document.getElementById('notificationBtn').addEventListener('click', async function() {
        const data = await fetchNotifications();
        showNotificationsInModal(data.notifications);
        updateNotificationBadge(data.unread_count);
        
        var myModal = new bootstrap.Modal(document.getElementById('notificationModal'));
        myModal.show();
    });

    document.getElementById('markAllReadBtn').addEventListener('click', async () => {
        try {
            await fetch('mark_notifications_read.php', { method: 'POST' });
            updateNotificationBadge(0);
            document.querySelectorAll('#notificationModalBody .notification-item').forEach(item => {
                item.classList.add('notification-read');
                item.dataset.read = 'true';
            });
            document.getElementById('markAllReadBtn').disabled = true;
        } catch (error) {
            console.error('Error marking notifications as read:', error);
        }
    });

    document.getElementById('clearAllBtn').addEventListener('click', async () => {
        if (confirm('Are you sure you want to clear all notifications?')) {
            try {
                await fetch('clear_notifications.php', { method: 'POST' });
                updateNotificationBadge(0);
                const modalBody = document.getElementById('notificationModalBody');
                modalBody.innerHTML = '<div class="text-center py-4"><i class="bi bi-check-circle-fill text-success" style="font-size: 2rem;"></i><p class="mt-2 text-muted">All notifications have been cleared.</p></div>';
                document.getElementById('markAllReadBtn').disabled = true;
                document.getElementById('clearAllBtn').disabled = true;
            } catch (error) {
                console.error('Error clearing notifications:', error);
            }
        }
    });

    document.getElementById('notificationModalBody').addEventListener('click', async (event) => {
        const notificationItem = event.target.closest('.notification-item');

        if (notificationItem && !notificationItem.classList.contains('disabled')) {
            event.preventDefault();

            const notificationId = notificationItem.dataset.id;
            const isAlreadyRead = notificationItem.dataset.read === 'true';

            if (!isAlreadyRead) {
                try {
                    const response = await fetch('mark_single_notification_read.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: notificationId })
                    });
                    const result = await response.json();

                    if (result.success) {
                        notificationItem.classList.add('notification-read');
                        notificationItem.dataset.read = 'true';
                        
                        const badge = document.getElementById('notificationBadge');
                        let count = parseInt(badge.textContent || '0');
                        if (count > 0) {
                            updateNotificationBadge(count - 1);
                        }
                    }
                } catch (error) {
                    console.error('Error marking notification as read:', error);
                }
            }
            window.location.href = notificationItem.href;
        }
    });

    document.getElementById('notificationModal').addEventListener('show.bs.modal', function () {
        const unreadCount = parseInt(document.getElementById('notificationBadge').textContent || '0');
        document.getElementById('markAllReadBtn').disabled = unreadCount === 0;
        
        // We need to fetch notifications to know the total count for the clear button
        fetchNotifications().then(data => {
            document.getElementById('clearAllBtn').disabled = data.notifications.length === 0;
        });
    });

    // Initial fetch for badge count
    fetchNotifications().then(data => {
      updateNotificationBadge(data.unread_count);
    });
</script>

</body>
</html>