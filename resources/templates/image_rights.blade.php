<h1>Autorização de Uso e Cessão de Direitos de Imagem</h1>

<p>
    Este instrumento é firmado por <strong>{{ $authorized_person_name }}</strong>,
    portador do CPF nº <strong>{{ $authorized_person_cpf }}</strong>, residente em
    <strong>{{ $authorized_person_address }}</strong>, com e-mail
    <strong>{{ $authorized_person_email }}</strong>, que autoriza o uso da sua imagem
    para as finalidades descritas neste documento.
</p>

<h2>1. Objeto</h2>

<p>
    Esta autorização permite a utilização da imagem, fotografia, arte ou material
    audiovisual identificado neste instrumento, produzido por
    <strong>{{ $image_producer_name }}</strong>, para a finalidade específica de
    <strong>{{ $image_usage_purpose }}</strong>.
</p>

<h2>2. Descrição da Imagem</h2>

<p>
    A imagem autorizada é: <strong>{{ $image_description }}</strong>. O material e sua
    modalidade são: <strong>{{ $image_material_type }}</strong>.
</p>

<h2>3. Prazo e Território</h2>

<p>
    A autorização é válida por prazo indeterminado e poderá ser utilizada em todo o
    território nacional e internacional, nas modalidades e meios permitidos por este
    instrumento, desde que sejam respeitadas a legislação aplicável e a dignidade da
    pessoa autorizadora.
</p>

<h2>4. Cessão dos Direitos de Uso</h2>

<p>
    A partir da assinatura deste documento, <strong>{{ $authorized_person_name }}</strong>
    cede ao proprietário do site <strong>{{ $site_owner_name }}</strong>, identificado
    como <strong>{{ $site_name }}</strong>, com domínio <strong>{{ $site_domain }}</strong>,
    os direitos de uso, reprodução, edição, adaptação, comunicação, publicação e
    distribuição da imagem autorizada.
</p>

<p>
    A cessão é definitiva, irrevogável e irretratável, sem número limitado de publicações
    ou edições, podendo abranger sites, redes sociais, blogs, materiais impressos,
    campanhas publicitárias, conteúdos jornalísticos, fotográficos, audiovisuais e
    demais meios de comunicação.
</p>

<h2>5. Declaração do Cedente</h2>

<p>
    O cedente declara estar ciente da utilização da sua imagem no site
    <strong>{{ $site_name }}</strong> e nos demais canais de divulgação relacionados a esta
    autorização, manifestando a sua livre e expressa vontade, sem prejuízo dos direitos
    previstos em lei.
</p>

<h2>6. Foro</h2>

<p>
    Fica eleita a cidade de <strong>{{ $forum_city }}</strong> para resolver eventuais
    litígios oriundos deste instrumento, com renúncia expressa a qualquer outro foro, na
    forma da legislação aplicável.
</p>

@if($is_minor)
    <h2>7. Autorização do Menor de Idade</h2>

    <p>
        Eu, <strong>{{ $legal_representative_name }}</strong>, na qualidade de
        <strong>{{ $legal_representative_relationship }}</strong>, responsável legal pelo(a)
        menor <strong>{{ $minor_name }}</strong>, nascido(a) em
        <strong>{{ $minor_birth_date }}</strong>, autorizo o uso e a cessão da imagem
        descrita neste instrumento, declarando possuir legitimidade legal para essa
        autorização.
    </p>
@endif

<h2>Identificação das partes</h2>

<p>Data: <strong>{{ $authorization_date }}</strong>.</p>

@if($is_minor)
    <p>
        <strong>{{ $legal_representative_name }}</strong> — Responsável legal —
        CPF: <strong>{{ $legal_representative_document }}</strong>
    </p>

    <p><strong>{{ $minor_name }}</strong> — menor de idade.</p>
@else
    <p>
        <strong>{{ $authorized_person_name }}</strong> —
        CPF: <strong>{{ $authorized_person_cpf }}</strong>
    </p>
@endif

<p>
    <strong>{{ $site_owner_name }}</strong> — proprietário do site
    <strong>{{ $site_name }}</strong>
</p>
