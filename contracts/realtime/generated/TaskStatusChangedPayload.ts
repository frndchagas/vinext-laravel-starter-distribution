import type { TaskState } from "./TaskState";

export interface TaskStatusChangedPayload {
  /** Opaque Task identifier. */
  id: string;
  state: TaskState;
  /** Increases by one on every persisted transition. Consumers must ignore versions older than the newest one seen. */
  version: number;
  occurred_at: string;
  /** Correlation ID of the request that started the Task. */
  correlation_id: string;
}
