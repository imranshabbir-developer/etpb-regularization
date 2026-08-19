<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Administrative geography of Pakistan, down to tehsil level for the districts
 * where Evacuee Trust properties are concentrated, plus the ETPB offices.
 *
 * Mouzas are deliberately NOT seeded — a mouza list is district-specific
 * revenue data that must come from the provincial Board of Revenue. Districts
 * capture them through the masters screen.
 */
class GeographySeeder extends Seeder
{
    public function run(): void
    {
        $revenueProfileId = DB::table('unit_conversion_profiles')
            ->where('code', 'REVENUE')->value('id');

        $provinces = [
            ['PB', 'Punjab', 'پنجاب'],
            ['SD', 'Sindh', 'سندھ'],
            ['KP', 'Khyber Pakhtunkhwa', 'خیبر پختونخوا'],
            ['BL', 'Balochistan', 'بلوچستان'],
            ['GB', 'Gilgit-Baltistan', 'گلگت بلتستان'],
            ['AJK', 'Azad Jammu and Kashmir', 'آزاد جموں و کشمیر'],
            ['ICT', 'Islamabad Capital Territory', 'وفاقی دارالحکومت'],
        ];

        $provinceIds = [];
        foreach ($provinces as [$code, $name, $nameUr]) {
            $provinceIds[$code] = DB::table('provinces')->insertGetId([
                'code' => $code, 'name' => $name, 'name_ur' => $nameUr,
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // province => [division => [district, ...]]
        $structure = [
            'PB' => [
                'Lahore'      => ['Lahore', 'Kasur', 'Sheikhupura', 'Nankana Sahib'],
                'Rawalpindi'  => ['Rawalpindi', 'Attock', 'Chakwal', 'Jhelum'],
                'Faisalabad'  => ['Faisalabad', 'Jhang', 'Toba Tek Singh', 'Chiniot'],
                'Multan'      => ['Multan', 'Khanewal', 'Lodhran', 'Vehari'],
                'Gujranwala'  => ['Gujranwala', 'Gujrat', 'Sialkot', 'Narowal', 'Mandi Bahauddin', 'Hafizabad'],
                'Sahiwal'     => ['Sahiwal', 'Okara', 'Pakpattan'],
                'Bahawalpur'  => ['Bahawalpur', 'Bahawalnagar', 'Rahim Yar Khan'],
                'Sargodha'    => ['Sargodha', 'Khushab', 'Mianwali', 'Bhakkar'],
                'Dera Ghazi Khan' => ['Dera Ghazi Khan', 'Layyah', 'Muzaffargarh', 'Rajanpur'],
            ],
            'SD' => [
                'Karachi'    => ['Karachi East', 'Karachi West', 'Karachi Central', 'Karachi South', 'Korangi', 'Malir'],
                'Hyderabad'  => ['Hyderabad', 'Badin', 'Dadu', 'Jamshoro', 'Matiari', 'Tando Allahyar', 'Thatta'],
                'Sukkur'     => ['Sukkur', 'Ghotki', 'Khairpur'],
                'Larkana'    => ['Larkana', 'Jacobabad', 'Shikarpur', 'Kashmore'],
                'Mirpur Khas' => ['Mirpur Khas', 'Tharparkar', 'Umerkot'],
                'Shaheed Benazirabad' => ['Shaheed Benazirabad', 'Naushahro Feroze', 'Sanghar'],
            ],
            'KP' => [
                'Peshawar'   => ['Peshawar', 'Charsadda', 'Nowshera', 'Khyber', 'Mohmand'],
                'Mardan'     => ['Mardan', 'Swabi'],
                'Hazara'     => ['Abbottabad', 'Haripur', 'Mansehra', 'Battagram', 'Torghar', 'Kolai Palas'],
                'Malakand'   => ['Swat', 'Dir Lower', 'Dir Upper', 'Chitral', 'Buner', 'Shangla', 'Malakand'],
                'Kohat'      => ['Kohat', 'Karak', 'Hangu', 'Kurram', 'Orakzai'],
                'Bannu'      => ['Bannu', 'Lakki Marwat', 'North Waziristan'],
                'Dera Ismail Khan' => ['Dera Ismail Khan', 'Tank', 'South Waziristan'],
            ],
            'BL' => [
                'Quetta'     => ['Quetta', 'Pishin', 'Killa Abdullah', 'Chaman'],
                'Kalat'      => ['Kalat', 'Khuzdar', 'Mastung', 'Lasbela', 'Awaran'],
                'Makran'     => ['Gwadar', 'Kech', 'Panjgur'],
                'Sibi'       => ['Sibi', 'Ziarat', 'Harnai', 'Kohlu', 'Dera Bugti'],
                'Nasirabad'  => ['Nasirabad', 'Jaffarabad', 'Jhal Magsi', 'Kachhi', 'Sohbatpur'],
                'Zhob'       => ['Zhob', 'Killa Saifullah', 'Sherani', 'Loralai', 'Musakhel', 'Barkhan'],
            ],
            'GB' => [
                'Gilgit'     => ['Gilgit', 'Hunza', 'Nagar', 'Ghizer'],
                'Baltistan'  => ['Skardu', 'Ghanche', 'Shigar', 'Kharmang'],
                'Diamer'     => ['Diamer', 'Astore'],
            ],
            'AJK' => [
                'Muzaffarabad' => ['Muzaffarabad', 'Hattian Bala', 'Neelum'],
                'Mirpur'       => ['Mirpur', 'Bhimber', 'Kotli'],
                'Poonch'       => ['Poonch', 'Bagh', 'Haveli', 'Sudhanoti'],
            ],
            'ICT' => [
                'Islamabad' => ['Islamabad'],
            ],
        ];

        $districtIds = [];
        foreach ($structure as $pCode => $divisions) {
            foreach ($divisions as $divName => $districts) {
                $divCode = $pCode . '-' . strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $divName), 0, 6));
                $divId = DB::table('divisions')->insertGetId([
                    'province_id' => $provinceIds[$pCode],
                    'code' => $divCode,
                    'name' => $divName,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($districts as $dName) {
                    $base = strtoupper(preg_replace('/[^A-Za-z]/', '', $dName));
                    $dCode = $pCode . '-' . substr($base, 0, 8);
                    // Keep district codes unique where names collapse to the same prefix.
                    $suffix = 1;
                    while (in_array($dCode, $districtIds, true)) {
                        $dCode = $pCode . '-' . substr($base, 0, 7) . $suffix++;
                    }
                    $districtIds[$dName] = $dCode;

                    DB::table('districts')->insert([
                        'province_id'     => $provinceIds[$pCode],
                        'division_id'     => $divId,
                        'code'            => $dCode,
                        'name'            => $dName,
                        'unit_profile_id' => $revenueProfileId,
                        'is_active'       => true,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            }
        }

        $this->tehsils();
        $this->offices();
    }

    /**
     * Tehsils for the districts that carry the bulk of urban evacuee trust
     * property. The rest are captured through the masters screen.
     */
    private function tehsils(): void
    {
        $map = [
            'Lahore'     => ['Lahore City', 'Lahore Cantt', 'Model Town', 'Raiwind', 'Shalimar'],
            'Rawalpindi' => ['Rawalpindi', 'Gujar Khan', 'Kahuta', 'Kallar Syedan', 'Murree', 'Taxila', 'Kotli Sattian'],
            'Faisalabad' => ['Faisalabad City', 'Faisalabad Sadar', 'Chak Jhumra', 'Jaranwala', 'Samundri', 'Tandlianwala'],
            'Multan'     => ['Multan City', 'Multan Sadar', 'Shujabad', 'Jalalpur Pirwala'],
            'Gujranwala' => ['Gujranwala City', 'Gujranwala Sadar', 'Kamoke', 'Nowshera Virkan', 'Wazirabad'],
            'Sialkot'    => ['Sialkot', 'Daska', 'Pasrur', 'Sambrial'],
            'Peshawar'   => ['Peshawar City', 'Peshawar Sadar', 'Chamkani', 'Badhber', 'Mathra'],
            'Hyderabad'  => ['Hyderabad City', 'Latifabad', 'Qasimabad', 'Hyderabad Rural'],
            'Quetta'     => ['Quetta City', 'Quetta Sadar', 'Panjpai', 'Chiltan'],
            'Islamabad'  => ['Islamabad Urban', 'Islamabad Rural'],
            'Bahawalpur' => ['Bahawalpur City', 'Bahawalpur Sadar', 'Ahmadpur East', 'Hasilpur', 'Khairpur Tamewali', 'Yazman'],
            'Sargodha'   => ['Sargodha', 'Bhalwal', 'Kot Momin', 'Sahiwal', 'Shahpur', 'Sillanwali'],
        ];

        foreach ($map as $districtName => $tehsils) {
            $districtId = DB::table('districts')->where('name', $districtName)->value('id');
            if (! $districtId) {
                continue;
            }
            $i = 1;
            foreach ($tehsils as $tName) {
                DB::table('tehsils')->insert([
                    'district_id' => $districtId,
                    'code'        => 'T-' . $districtId . '-' . $i++,
                    'name'        => $tName,
                    'is_active'   => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    private function offices(): void
    {
        DB::table('offices')->insert([
            'code'        => 'ETPB-HO',
            'name'        => 'ETPB Head Office, Lahore',
            'office_type' => 'HEAD_OFFICE',
            'district_id' => DB::table('districts')->where('name', 'Lahore')->value('id'),
            'province_id' => DB::table('provinces')->where('code', 'PB')->value('id'),
            'address'     => '65-A, Shahrah-e-Quaid-e-Azam, Lahore',
            'is_active'   => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // A district office for each district that has tehsils seeded.
        $districts = DB::table('districts')
            ->whereIn('name', [
                'Lahore', 'Rawalpindi', 'Faisalabad', 'Multan', 'Gujranwala', 'Sialkot',
                'Peshawar', 'Hyderabad', 'Quetta', 'Islamabad', 'Bahawalpur', 'Sargodha',
            ])->get();

        foreach ($districts as $d) {
            DB::table('offices')->insert([
                'code'        => 'ETPB-' . $d->code,
                'name'        => 'ETPB District Office, ' . $d->name,
                'office_type' => 'DISTRICT',
                'district_id' => $d->id,
                'province_id' => $d->province_id,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}
