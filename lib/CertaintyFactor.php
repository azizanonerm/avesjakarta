<?php
require_once __DIR__ . '/../config/certainty.php';
/**
 * Certainty Factor Algorithm Implementation
 * AVES Jakarta Expert System
 * 
 * Rumus CF:
 * CF[H,E] = CF[Pakar] * CF[User]
 * CF_combine = CF1 + CF2 * (1 - CF1)
 */

class CertaintyFactor {
    
    /**
     * Hitung CF untuk satu gejala
     * CF[H,E] = CF[Pakar] * CF[User]
     */
    public static function calculateSingleCF($cf_pakar, $cf_user) {
        return $cf_pakar * $cf_user;
    }
    
    /**
     * Kombinasikan dua CF
     * CF_combine = CF1 + CF2 * (1 - CF1)
     */
    public static function combineCF($cf1, $cf2) {
        if ($cf1 < 0 || $cf2 < 0) {
            return 0;
        }
        return $cf1 + $cf2 * (1 - $cf1);
    }
    
    /**
     * Kombinasikan multiple CF values
     */
    public static function combineMultipleCF($cf_values) {
        if (empty($cf_values)) {
            return 0;
        }
        
        $result = $cf_values[0];
        for ($i = 1; $i < count($cf_values); $i++) {
            $result = self::combineCF($result, $cf_values[$i]);
        }
        
        return $result;
    }
    
    /**
     * Diagnosa berdasarkan gejala yang dipilih user
     * 
     * @param array $selected_symptoms Format: ['id_gejala' => cf_user, ...]
     * @param PDO $pdo Database connection
     * @return array Hasil diagnosa terurut
     */
    public static function diagnose($selected_symptoms, $pdo) {
        $results = [];
        
        // Ambil semua penyakit
        $stmt = $pdo->prepare('SELECT id_penyakit, kode_penyakit, nama_penyakit, kategori, status_validasi FROM penyakit');
        $stmt->execute();
        $diseases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($diseases as $disease) {
            $disease_id = $disease['id_penyakit'];
            $cf_values = [];
            $rule_details = [];
            
            // Ambil semua aturan untuk penyakit ini
            $stmt = $pdo->prepare('
                SELECT a.id_aturan, a.id_gejala, a.cf_pakar,
                       g.kode_gejala, g.nama_gejala
                FROM aturan a
                JOIN gejala g ON g.id_gejala = a.id_gejala
                WHERE a.id_penyakit = ?
                ORDER BY g.kode_gejala
            ');
            $stmt->execute([$disease_id]);
            $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Hitung CF untuk setiap aturan
            foreach ($rules as $rule) {
                $symptom_id = $rule['id_gejala'];
                $cf_pakar = $rule['cf_pakar'];
                
                // Jika gejala dipilih user
                if (isset($selected_symptoms[$symptom_id]) && (float) $selected_symptoms[$symptom_id] > 0) {
                    $cf_user = $selected_symptoms[$symptom_id];
                    $cf_evidence = self::calculateSingleCF($cf_pakar, $cf_user);
                    
                    $cf_values[] = $cf_evidence;
                    
                    // Simpan detail untuk transparansi
                    $rule_details[] = [
                        'id_aturan' => $rule['id_aturan'],
                        'id_gejala' => $symptom_id,
                        'kode_gejala' => $rule['kode_gejala'],
                        'nama_gejala' => $rule['nama_gejala'],
                        'cf_pakar' => $cf_pakar,
                        'cf_user' => $cf_user,
                        'cf_evidence' => $cf_evidence
                    ];
                }
            }
            
            // Penyakit tanpa satu pun gejala cocok tidak dimasukkan ke ranking.
            if (empty($cf_values)) {
                continue;
            }

            // Kombinasikan semua CF positif sesuai rumus CF pada skripsi.
            $final_cf = self::combineMultipleCF($cf_values);
            
            // Pembulatan ke 3 desimal
            $final_cf = round($final_cf, 3);
            
            $results[] = [
                'id_penyakit' => $disease_id,
                'kode_penyakit' => $disease['kode_penyakit'],
                'nama_penyakit' => $disease['nama_penyakit'],
                'kategori' => $disease['kategori'],
                'status_validasi' => $disease['status_validasi'],
                'cf_final' => $final_cf,
                'cf_percentage' => round($final_cf * 100, 1),
                'matched_symptoms' => count($rule_details),
                'rule_details' => $rule_details
            ];
        }
        
        // Urutkan berdasarkan CF tertinggi
        usort($results, function($a, $b) {
            return $b['cf_final'] <=> $a['cf_final'];
        });
        
        return $results;
    }
    
    /**
     * Konversi CF ke tingkat kepastian
     */
    public static function getCertaintyLevel($cf) {
        return getCertaintyInterpretation($cf);
    }
}
?>
