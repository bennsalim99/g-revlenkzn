<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Görevleri getir
$stmt = $db->prepare("
    SELECT t.*, u.username as creator_username, 
           COUNT(DISTINCT tp.user_id) as participant_count,
           CASE WHEN EXISTS (
               SELECT 1 FROM task_participants 
               WHERE task_id = t.id AND user_id = ?
           ) THEN 1 ELSE 0 END as is_participating
    FROM tasks t
    LEFT JOIN users u ON t.creator_id = u.id
    LEFT JOIN task_participants tp ON t.id = tp.task_id
    WHERE t.status = 'active'
    GROUP BY t.id
    ORDER BY t.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$tasks = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ana Sayfa - Görev Yap Kazan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .task-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .task-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #ddd;
            border-radius: 8px 8px 0 0;
        }
        .task-body {
            padding: 15px;
        }
        .task-footer {
            background-color: #f8f9fa;
            padding: 15px;
            border-top: 1px solid #ddd;
            border-radius: 0 0 8px 8px;
        }
        .points-badge {
            font-size: 24px;
            font-weight: bold;
            color: #198754;
            background-color: #e8f5e9;
            padding: 8px 16px;
            border-radius: 8px;
            display: inline-block;
            margin: 10px 0;
        }
        .points-icon {
            color: #ffd700;
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">Aktif Görevler</h2>

        <?php if (empty($tasks)): ?>
            <div class="alert alert-info">Henüz aktif görev bulunmuyor.</div>
        <?php else: ?>
            <?php foreach ($tasks as $task): ?>
                <div class="task-card">
                    <div class="task-header">
                        <h4 class="mb-0"><?php echo clean($task['title']); ?></h4>
                    </div>
                    <div class="task-body">
                        <p><?php echo nl2br(clean($task['description'])); ?></p>
                        
                        <div class="task-meta">
                            <div>
                                <i class="fas fa-user"></i>
                                <?php echo clean($task['creator_username']); ?>
                            </div>
                            <div>
                                <i class="fas fa-users"></i>
                                <?php echo $task['current_participants']; ?>/<?php echo $task['max_participants']; ?>
                            </div>
                            <div style="background: #ffd700; color: #000; padding: 5px 15px; border-radius: 20px; font-size: 1.2em; font-weight: bold;">
                                <i class="fas fa-coins" style="color: #b8860b;"></i>
                                <?php echo number_format($task['points_reward']); ?> Puan
                            </div>
                        </div>
                        
                        <?php if (!empty($task['task_link'])): ?>
                            <a href="<?php echo clean($task['task_link']); ?>" target="_blank" class="btn btn-primary btn-sm">
                                <i class="fas fa-external-link-alt"></i> Göreve Git
                            </a>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-calendar"></i> Tarih: <?php echo date('d.m.Y H:i', strtotime($task['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                    <div class="task-footer">
                        <?php if ($task['is_participating']): ?>
                            <button class="btn btn-secondary" disabled>
                                <i class="fas fa-check"></i> Katıldınız
                            </button>
                        <?php elseif ($task['creator_id'] == $_SESSION['user_id']): ?>
                            <button class="btn btn-secondary" disabled>
                                <i class="fas fa-info-circle"></i> Sizin Göreviniz
                            </button>
                        <?php elseif ($task['participant_count'] >= $task['max_participants']): ?>
                            <button class="btn btn-secondary" disabled>
                                <i class="fas fa-users-slash"></i> Görev Dolu
                            </button>
                        <?php else: ?>
                            <button class="btn btn-success" onclick="joinTask(<?php echo $task['id']; ?>)">
                                <i class="fas fa-plus"></i> Göreve Katıl
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <script>
        function joinTask(taskId) {
            if (confirm('Bu göreve katılmak istediğinize emin misiniz?')) {
                fetch('join_task.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'task_id=' + taskId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                });
            }
        }
    </script>
</body>
</html>