# SPDX-FileCopyrightText: 2026 G DATA CyberDefense AG <vaas@gdata.de>
# SPDX-License-Identifier: AGPL-3.0-or-later

FROM mcr.microsoft.com/devcontainers/base:trixie

ENV DEBIAN_FRONTEND=noninteractive

RUN \
    mkdir -p ~/.local/bin \
    && echo "export PATH=\$PATH:~/.local/bin" >> ~/.bashrc

ARG USERNAME=vscode
ARG USER_UID=1000
ARG USER_GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        bash-completion \
        bats \
        ca-certificates \
        curl \
        git \
        gnupg \
        iputils-ping \
        jq \
        lsb-release \
        make \
        procps \
        sudo \
        telnet \
        unzip \
        vim \
        zip \
    && mkdir -p /etc/apt/keyrings \
    && curl -fsSL https://cli.github.com/packages/githubcli-archive-keyring.gpg | gpg --dearmor -o /etc/apt/keyrings/githubcli.gpg \
    && chmod go+r /etc/apt/keyrings/githubcli.gpg \
    && echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/githubcli.gpg] https://cli.github.com/packages stable main" > /etc/apt/sources.list.d/github-cli.list \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_20.x nodistro main" > /etc/apt/sources.list.d/nodesource.list \
    && curl -fsSL https://download.docker.com/linux/debian/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg \
    && chmod go+r /etc/apt/keyrings/docker.gpg \
    && echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/debian $(. /etc/os-release && echo \"$VERSION_CODENAME\") stable" > /etc/apt/sources.list.d/docker.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        composer \
        docker-ce \
        docker-ce-cli \
        docker-buildx-plugin \
        docker-compose-plugin \
        gh \
        nodejs \
        php \
        php-cli \
        php-curl \
        php-gd \
        php-intl \
        php-mbstring \
        php-xml \
        php-zip \
        shellcheck \
    && if ! getent group "$USERNAME" >/dev/null; then groupadd --gid "$USER_GID" "$USERNAME"; fi \
    && if ! id -u "$USERNAME" >/dev/null 2>&1; then useradd --uid "$USER_UID" --gid "$USER_GID" -m -s /bin/bash "$USERNAME"; fi \
    && usermod -aG docker "$USERNAME" \
    && echo "$USERNAME ALL=(ALL) NOPASSWD:ALL" > "/etc/sudoers.d/$USERNAME" \
    && chmod 0440 "/etc/sudoers.d/$USERNAME" \
    && rm -rf /var/lib/apt/lists/*

# Save command line history
RUN echo "export HISTFILE=/home/vscode/commandhistory/.bash_history" >> "/home/vscode/.bashrc" \
    && echo "export PROMPT_COMMAND='history -a'" >> "/home/vscode/.bashrc" \
    && mkdir -p /home/vscode/commandhistory \
    && touch /home/vscode/commandhistory/.bash_history \
    && chown -R vscode /home/vscode/commandhistory    

CMD ["sleep", "infinity"]
