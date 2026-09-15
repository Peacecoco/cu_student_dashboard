<?php
class General
{
    protected PDO $db;

    public function __construct(PDO $con)
    {
        $this->db = $con;
    }

    protected function sendResponse(bool $success, string $message, mixed $data = null, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        $response = ['success' => $success, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        echo json_encode($response);
        exit;
    }

    protected function sanitize(mixed $value): string
    {
        return trim(strip_tags((string) $value));
    }

    protected function validateRequired(array $data, array $required): array
    {
        return array_values(array_filter($required, static fn(string $field): bool => empty($data[$field])));
    }

    protected function getApplicantByIdentifier(string $identifier): ?array
    {
        $identifier = $this->sanitize($identifier);
        $student = $this->db->prepare("SELECT id AS personid, matric_no AS identifier, full_name AS fullname, 'student' AS persontype, department, photo_path AS registeredphotopath, NULL AS role FROM students WHERE matric_no = ? LIMIT 1");
        $student->execute([$identifier]);
        return $student->fetch() ?: null;
    }

    protected function getIdCardSettings(string $applicationType): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM idcardsettings WHERE applicationtype = ? AND isactive = 1 LIMIT 1');
        $statement->execute([$applicationType]);
        return $statement->fetch() ?: null;
    }

    protected function buildFileUrl(?string $relativePath): ?string
    {
        if (!$relativePath) {
            return null;
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        return $scheme . '://' . $host . $base . '/' . ltrim(str_replace('\\', '/', $relativePath), '/');
    }
}
