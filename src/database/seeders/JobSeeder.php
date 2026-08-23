<?php

namespace Database\Seeders;

use App\Models\JobApplication;
use App\Models\JobPost;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class JobSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::pluck('id', 'email');

        $posts = [
            [
                'poster'       => 'hr@aqdesk.test',
                'title'        => 'Junior Laravel Developer',
                'company'      => 'CodeCraft Solutions',
                'type'         => 'job',
                'work_mode'    => 'hybrid',
                'location'     => 'Karachi, Pakistan',
                'description'  => "We are looking for a junior developer to join our web team.\n\nYou will work on client applications built with Laravel and MySQL, alongside two senior engineers who will review your code and mentor you through your first year.",
                'requirements' => "• Comfortable with PHP and at least one MVC framework\n• Understands relational database design\n• Familiar with Git\n• Fresh graduates are welcome to apply",
                'salary_min'   => 70000,
                'salary_max'   => 110000,
                'deadline'     => 30,
                'applicants'   => ['member@aqdesk.test', 'faisal@aqdesk.test'],
            ],
            [
                'poster'       => 'expert@aqdesk.test',
                'title'        => 'Research Internship — AI in Education',
                'company'      => 'National University of Sciences',
                'type'         => 'internship',
                'work_mode'    => 'onsite',
                'location'     => 'Lahore, Pakistan',
                'description'  => "A six month research internship investigating how large language models can support formative assessment in undergraduate courses.\n\nYou will help build datasets, run experiments and co-author the write-up.",
                'requirements' => "• Studying Computer Science, Data Science or a related field\n• Python and basic machine learning coursework\n• Strong written English",
                'salary_min'   => 40000,
                'salary_max'   => null,
                'deadline'     => 21,
                'applicants'   => ['sara@aqdesk.test'],
            ],
            [
                'poster'       => 'hr@aqdesk.test',
                'title'        => 'Frontend Engineer (Accessibility Focus)',
                'company'      => 'CodeCraft Solutions',
                'type'         => 'job',
                'work_mode'    => 'remote',
                'location'     => 'Remote (Pakistan)',
                'description'  => "Own the accessibility of our product front end.\n\nYou will audit existing screens against WCAG 2.1 AA, fix what you find, and set up automated checks so regressions get caught in CI.",
                'requirements' => "• Solid HTML semantics and ARIA knowledge\n• Experience with screen readers\n• JavaScript and a modern framework",
                'salary_min'   => 120000,
                'salary_max'   => 180000,
                'deadline'     => 45,
                'applicants'   => ['faisal@aqdesk.test'],
            ],
            [
                'poster'       => 'expert@aqdesk.test',
                'title'        => 'Teaching Assistant — Web Technologies',
                'company'      => 'National University of Sciences',
                'type'         => 'internship',
                'work_mode'    => 'onsite',
                'location'     => 'Lahore, Pakistan',
                'description'  => "Support the delivery of an undergraduate web technologies course: run lab sessions, mark assignments and hold weekly office hours.",
                'requirements' => "• Completed a web development course with a strong grade\n• Comfortable explaining technical ideas to first year students",
                'salary_min'   => 25000,
                'salary_max'   => null,
                'deadline'     => 14,
                'applicants'   => [],
            ],
            [
                'poster'       => 'hr@aqdesk.test',
                'title'        => 'Data Analyst Intern',
                'company'      => 'Insight Analytics',
                'type'         => 'internship',
                'work_mode'    => 'hybrid',
                'location'     => 'Islamabad, Pakistan',
                'description'  => "Work with our analytics team on dashboards and reporting for client accounts. A good first role for someone who enjoys turning messy data into clear answers.",
                'requirements' => "• SQL fundamentals\n• Excel or a BI tool\n• Python is a plus",
                'salary_min'   => 35000,
                'salary_max'   => 50000,
                'deadline'     => 25,
                'applicants'   => ['sara@aqdesk.test', 'member@aqdesk.test'],
            ],
        ];

        $coverLetters = [
            "I am in the final year of my Computer Science degree and have built several Laravel applications, including a community knowledge platform with role-based access, a Q&A module and a jobs board.\n\nI am comfortable with MySQL schema design and have used Git throughout my final year project. I would value the mentoring this role offers and can start immediately after my submission deadline.",
            "My coursework and personal projects have focused on exactly the area this role covers, and I have been reading around the subject well beyond what my degree requires.\n\nI have attached my CV and would welcome the chance to talk about how I could contribute.",
            "I care a great deal about building interfaces that everyone can use, and I have spent the last year auditing my own projects against WCAG and fixing what I found.\n\nI would like to do that work full time, and I would bring both the technical skills and the habit of testing with a keyboard and a screen reader rather than assuming.",
        ];

        foreach ($posts as $index => $data) {
            $job = JobPost::updateOrCreate(
                ['slug' => Str::slug($data['title'])],
                [
                    'user_id'      => $users[$data['poster']],
                    'title'        => $data['title'],
                    'company'      => $data['company'],
                    'type'         => $data['type'],
                    'work_mode'    => $data['work_mode'],
                    'location'     => $data['location'],
                    'description'  => $data['description'],
                    'requirements' => $data['requirements'],
                    'salary_min'   => $data['salary_min'],
                    'salary_max'   => $data['salary_max'],
                    'deadline'     => now()->addDays($data['deadline'])->toDateString(),
                    'is_active'    => true,
                ]
            );

            // created_at is guarded (not in $fillable), so it is set directly.
            $job->created_at = now()->subDays(count($posts) - $index);
            $job->save();

            foreach ($data['applicants'] as $i => $email) {
                $application = JobApplication::updateOrCreate(
                    [
                        'job_post_id' => $job->id,
                        'user_id'     => $users[$email],
                    ],
                    [
                        'cover_letter' => $coverLetters[$i % count($coverLetters)],
                        'status'       => ['pending', 'reviewed', 'shortlisted'][$i % 3],
                    ]
                );

                $application->created_at = $job->created_at->copy()->addDays(1);
                $application->save();
            }
        }
    }
}
