<?php 
namespace App\Models\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

trait HasAuditTrail
{
  public function auditLogs()
  {
    return $this->hasMany(AuditLog::class);
  }

  /**
   * Log an activity for this user.
   *
   * @param string $module e.g. inventory, crm
   * @param string $action e.g. stock_entries.created
   * @param string|null $description human readable message
   * @param Model|null $subject optional related model (e.g. StockEntry)
   * @param array $meta optional extra metadata (e.g. changes)
   */
  public function logActivity(
    string $module,
    string $action,
    ?string $description = null,
    ?Model $subject = null,
    array $meta = []
  ): AuditLog {
    $req = request();

    $payload = [
      'user_id' => $this->id,
      'module' => $module,
      'action' => $action,
      'description' => $description,
      'route' => optional($req->route())->getName(),
      'url' => $req->fullUrl(),
      'method' => $req->method(),
      'ip' => $req->ip(),
      'user_agent' => $req->userAgent(),
      'meta' => $meta ?: null,
    ];

    if ($subject) {
      $payload['subject_type'] = get_class($subject);
      $payload['subject_id'] = $subject->getKey();
    }

    // Remove huge / sensitive data if accidentally passed
    if (!empty($payload['meta'])) {
      $payload['meta'] = Arr::except($payload['meta'], ['password', 'password_confirmation']);
    }

    return AuditLog::create($payload);
  }
}
