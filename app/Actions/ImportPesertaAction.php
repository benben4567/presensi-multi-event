<?php

namespace App\Actions;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Participant;
use App\Support\PhoneNumberNormalizer;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportPesertaAction
{
    /**
     * Import participants from an Excel/CSV file into an event.
     *
     * @return array{imported: int, skipped: int, errors: int, error_rows: list<array<string, mixed>>, skipped_rows: list<array<string, mixed>>}
     */
    public function execute(Event $event, string $filePath): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = 0;
        $errorRows = [];
        $skippedRows = [];

        $rows = (new FastExcel)->import($filePath);

        foreach ($rows as $index => $row) {
            $rowNum = (int) $index + 2;

            // Normalize column keys to lowercase.
            $row = array_change_key_case($row, CASE_LOWER);

            $nama = trim($row['nama'] ?? '');
            $noHp = trim((string) ($row['no_hp'] ?? ''));
            $email = trim((string) ($row['email'] ?? ''));

            if ($nama === '') {
                $errors++;
                $errorRows[] = ['baris' => $rowNum, 'no_hp' => $noHp, 'alasan' => 'Nama kosong'];

                continue;
            }

            $phoneE164 = PhoneNumberNormalizer::toE164($noHp);

            if ($phoneE164 === null) {
                $errors++;
                $errorRows[] = ['baris' => $rowNum, 'nama' => $nama, 'no_hp' => $noHp, 'alasan' => 'Nomor HP tidak valid'];

                continue;
            }

            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors++;
                $errorRows[] = ['baris' => $rowNum, 'nama' => $nama, 'no_hp' => $noHp, 'alasan' => 'Email tidak valid'];

                continue;
            }

            $email = $email !== '' ? $email : null;

            // Check if this phone is already enrolled in the event.
            $existingParticipant = Participant::where('phone_e164', $phoneE164)->first();

            // Collect extra columns as meta.
            $reservedKeys = ['nama', 'no_hp', 'email'];
            $meta = [];
            foreach ($row as $key => $value) {
                if (! in_array(strtolower($key), $reservedKeys)) {
                    $value = trim((string) $value);
                    if ($value !== '') {
                        $meta[$key] = $value;
                    }
                }
            }

            if ($existingParticipant) {
                $alreadyEnrolled = EventParticipant::where('event_id', $event->id)
                    ->where('participant_id', $existingParticipant->id)
                    ->exists();

                if ($alreadyEnrolled) {
                    $updates = [];
                    if (! empty($meta)) {
                        $updates['meta'] = array_merge($existingParticipant->meta ?? [], $meta);
                    }
                    if ($email !== null) {
                        $updates['email'] = $email;
                    }
                    if (! empty($updates)) {
                        $existingParticipant->update($updates);
                    }

                    $skipped++;
                    $skippedRows[] = ['baris' => $rowNum, 'nama' => $nama, 'no_hp' => $phoneE164];

                    continue;
                }
            }

            $participant = Participant::firstOrCreate(
                ['phone_e164' => $phoneE164],
                ['name' => $nama, 'email' => $email, 'meta' => empty($meta) ? null : $meta],
            );

            if (! $participant->wasRecentlyCreated) {
                $updates = [];
                if (! empty($meta)) {
                    $updates['meta'] = array_merge($participant->meta ?? [], $meta);
                }
                if ($email !== null) {
                    $updates['email'] = $email;
                }
                if (! empty($updates)) {
                    $participant->update($updates);
                }
            }

            (new EnrollParticipantAction)->execute($event, $participant);

            $imported++;
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'error_rows' => $errorRows,
            'skipped_rows' => $skippedRows,
        ];
    }
}
