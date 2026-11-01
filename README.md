
# Simple RAG Chatbot Using Laravel and Ollama

## Overview

This project is an early experimental implementation of **Retrieval-Augmented Generation (RAG)** using Laravel and a locally hosted Large Language Model (LLM) through Ollama.

The objective was to explore how a conversational AI application could retrieve relevant information from a database and use that information as context when generating responses.

The application was developed as a preliminary technical exploration of RAG concepts rather than a production-ready system.

## Technologies Used

* **PHP / Laravel:** Application development and request handling.
* **Ollama:** Local hosting and interaction with a language model.
* **Llama 3.2 (1B):** Used for embedding generation and conversational responses.
* **MySQL:** Storage and retrieval of documents and their embeddings.
* **Laravel HTTP Client:** Communication with Ollama's API.

## How It Works

The application follows a simplified RAG workflow:

1. **User Query:** A user submits a question through the chatbot interface.
2. **Embedding Generation:** The question is converted into a numerical embedding using the locally hosted model.
3. **Document Retrieval:** The application calculates similarity scores between the query embedding and stored document embeddings using SQL.
4. **Context Selection:** The three highest-scoring documents are retrieved.
5. **Prompt Construction:** Retrieved document content is combined with the user's question, instructing the LLM to answer using the supplied context.
6. **Response Generation:** The LLM generates a conversational response based on the retrieved information.

### Simplified Architecture

```text
         User Question
               |
               v
       Laravel Application
               |
               v
       Ollama Embedding API
               |
               v
       Query Embedding
               |
               v
       MySQL Document Store
       (Similarity Search)
               |
               v
       Top 3 Documents
               |
               v
       Context + User Question
               |
               v
       Ollama Generation API
               |
               v
       Generated Response
```

## Implementation Requirements

The application requires:

* A Laravel application with a configured database connection.
* A locally running Ollama service at `http://localhost:11434`.
* The `llama3.2:1b` model available through Ollama.
* A `documents` database table containing document titles, content, and embeddings.

Document embeddings must already exist in the database and be compatible with the embedding model used for user queries.

The implementation shown focuses on retrieval and response generation; document ingestion and embedding preparation are separate requirements.

## Example Interaction

A user may ask:

**Question:** What information is available about a particular topic?

The application retrieves the three highest-scoring documents and supplies their content to the language model, which attempts to generate an answer based on that context.

## Experimental Scope and Limitations

This project represents an initial attempt to understand and implement the RAG concept.

Several limitations remain:

* Document similarity is calculated using a basic dot-product approach rather than a dedicated vector-search system.
* Retrieval is restricted to the three highest-scoring documents, without a minimum relevance threshold.
* The model may generate incorrect or unsupported information despite being instructed to use only the retrieved context.
* The implementation does not include formal evaluation of retrieval accuracy or response quality.
* The approach has not been optimised for large document collections or production deployment.

These limitations are relevant to further investigations into knowledge grounding, response verification, and the reliability of LLM-based applications.

## Project Status

**Status: Experimental / Proof of Concept**

This project was developed as an independent technical exploration to gain practical experience with retrieval-augmented language models.

It demonstrates a basic approach to combining database retrieval with locally hosted language-model generation.

The project is not intended for production use and has not undergone formal performance, reliability, or security evaluation.

## Future Improvements

Potential areas for further development include:

* Using a dedicated embedding model and vector database.
* Improving document similarity and retrieval methods.
* Introducing relevance thresholds.
* Evaluating response accuracy and hallucination rates.
* Exploring independent verification of generated responses.

## Purpose

This repository documents an early practical exploration of retrieval-augmented generation and forms part of my broader interest in AI reliability, knowledge-based systems, and the development of trustworthy AI applications.


<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
# my-gpt-1
