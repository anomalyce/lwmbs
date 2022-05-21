<?php
/**
 * Copyright (c) 2022 Yun Dou <dixyes@gmail.com>
 *
 * lwmbs is licensed under Mulan PSL v2. You can use this
 * software according to the terms and conditions of the
 * Mulan PSL v2. You may obtain a copy of Mulan PSL v2 at:
 *
 * http://license.coscl.org.cn/MulanPSL2
 *
 * THIS SOFTWARE IS PROVIDED ON AN "AS IS" BASIS,
 * WITHOUT WARRANTIES OF ANY KIND, EITHER EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO NON-INFRINGEMENT,
 * MERCHANTABILITY OR FIT FOR A PARTICULAR PURPOSE.
 *
 * See the Mulan PSL v2 for more details.
 */

declare(strict_types=1);

class Liblibffi extends Library
{
    use LinuxLibraryTrait;
    protected string $name = 'libffi';
    protected array $staticLibs = [
        'libffi.a',
    ];
    protected array $headers = [
        'ffi.h',
        'ffitarget.h',
    ];
    protected array $pkgconfs = [
        'libffi.pc' => <<<'EOF'
exec_prefix=${prefix}
libdir=${exec_prefix}/lib
toolexeclibdir=${libdir}
includedir=${prefix}/include

Name: libffi
Description: Library supporting Foreign Function Interfaces
Version: 3.4.2
Libs: -L${toolexeclibdir} -lffi
Cflags: -I${includedir}
EOF
    ];
    protected array $depNames = [];

    protected function build(): void
    {
        Log::i("building {$this->name}");
        $ret = 0;
        $env = $this->config->configureEnv;
        switch ($this->config->libc) {
            case CLib::MUSL_WRAPPER:
                $env .= ' CC="' .
                    $this->config->libc->getCC() . ' ' .
                    '-static ' .
                    '-idirafter ' . realpath('include') . ' ' .
                    '-idirafter /usr/include/ ' .
                    '-idirafter /usr/include/x86_64-linux-gnu/"';
                break;
            case CLib::GLIBC:
                break;
            case CLib::MUSL:
                break;
            default:
                throw new Exception("unsupported libc: {$this->config->libc->name}");
        }
        passthru(
            $this->config->setX . ' && ' .
                "cd {$this->sourceDir} && " .
                "$env ./configure " .
                '--enable-static ' .
                '--disable-shared ' .
                '--prefix= ' . //use prefix=/
                '--libdir=/lib && ' .
                "make -j{$this->config->concurrency} && " .
                'make install DESTDIR=' . realpath('.'),
            $ret
        );
        if ($ret !== 0) {
            throw new Exception("failed to build {$this->name}");
        }
        $this->makeFakePkgconfs();
    }
}
