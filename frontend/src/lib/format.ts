const dateTime = new Intl.DateTimeFormat('pl-PL', { dateStyle: 'short', timeStyle: 'short' })

export function formatDateTime(iso: string): string {
  return dateTime.format(new Date(iso))
}

const queueLabels: Record<string, string> = {
  database: 'Baza danych',
  rabbitmq: 'RabbitMQ',
  redis: 'Redis',
  sync: 'Synchronicznie',
}

/** Readable name of a Laravel queue connection; unknown names are shown as they are. */
export function formatQueueConnection(connection: string | null): string {
  if (connection === null) {
    return '—'
  }

  return queueLabels[connection] ?? connection
}
