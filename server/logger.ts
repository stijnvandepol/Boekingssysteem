type LogLevel = "info" | "warn" | "error";

type LogPayload = {
  event: string;
  message: string;
  metadata?: Record<string, unknown>;
};

function emit(level: LogLevel, payload: LogPayload): void {
  const entry = {
    level,
    timestamp: new Date().toISOString(),
    ...payload
  };

  const serialized = JSON.stringify(entry);

  if (level === "error") {
    console.error(serialized);
    return;
  }

  if (level === "warn") {
    console.warn(serialized);
    return;
  }

  console.log(serialized);
}

export const logger = {
  info(payload: LogPayload): void {
    emit("info", payload);
  },
  warn(payload: LogPayload): void {
    emit("warn", payload);
  },
  error(payload: LogPayload): void {
    emit("error", payload);
  }
};
